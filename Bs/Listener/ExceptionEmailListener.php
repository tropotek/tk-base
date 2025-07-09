<?php
namespace Bs\Listener;

use Bs\Factory;
use Exception;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Throwable;
use Symfony\Component\HttpKernel\KernelEvents;
use Tk\Config;
use Tk\Log;
use Tk\Mail\Mailer;
use Tk\Str;
use Tk\System;
use Tk\Uri;


class ExceptionEmailListener implements EventSubscriberInterface
{

    protected string  $siteTitle = '';
    protected array   $emailList = [];


    public function __construct(array $emails, string $siteTitle = '')
    {
        $this->emailList = $emails;
        $this->siteTitle = $siteTitle;
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $this->emailException($event->getThrowable());
    }

    public function onConsoleError(ConsoleErrorEvent $event): void
    {
        $this->emailException($event->getError());
    }

    /**
     * @param Throwable $e
     */
    protected function emailException(Throwable $e): void
    {
        // These errors are not required they can cause email loops
        if ($e instanceof ResourceNotFoundException ||
            $e instanceof NotFoundHttpException ||
            $e instanceof MethodNotAllowedHttpException)
        {
            return;
        }

        try {
            if (count($this->emailList)) {
                foreach ($this->emailList as $email) {

                    $message = Factory::instance()->createMailMessage($this->getExceptionHtml($e));
                    $subject = "{$this->siteTitle} Error: '{$e->getMessage()}'";
                    $message->setSubject(Str::strcat($subject, 80));
                    $message->setFrom($email);
                    $message->addTo($email);
                    $message->addHeader('X-Exception', get_class($e));
                    $message->set('sig', '');

                    if (!Mailer::instance()->send($message)) {
                        Log::error("failed to send exception email to: {$email}");
                    }
                }
            }
        } catch (Exception $ee) { Log::notice($ee->__toString()); }
    }

    public function getExceptionHtml(Throwable $e, bool $withTrace = true): string
    {
        $class = get_class($e);
        $msg = $e->getMessage();
        $str = '';
        $extra = '';
        $logHtml = '';

        if ($withTrace) {
            $str = trim($e->__toString());
            $str = highlight_string("<?php \n" . $str, true);
            $str = str_replace(["&lt;?php", 'color: #FF8000'], ['', 'color: #666'], $str);
            $extra = sprintf('<br/> in <em>%s:%s</em>',  $e->getFile(), $e->getLine());
        }

        $uri = Uri::create()->toString();
        $ip = System::getClientIp();
        $request = print_r($_REQUEST, true);

        return <<<HTML
<div>
    <style>
        code, pre {
          line-height: 1.4em;
          padding: 0;margin: 0;
          overflow: auto;
        }
    </style>
    <h2>{$this->siteTitle} Error: $class</h2>
    <p><strong>$msg $extra</strong></p>
    <ul>
        <li><strong>URI:</strong> <span>{$uri}</span></li>
        <li><strong>Method:</strong> <span>{$_SERVER['REQUEST_METHOD']}</span></li>
        <li><strong>Remote IP:</strong> <span>{$ip}</span></li>
        <li><strong>Agent:</strong> <span>{$_SERVER['HTTP_USER_AGENT']}</span></li>
        <li>
            <strong>Request:</strong><br>
            <pre>{$request}</pre>
        </li>
    </ul>
    <pre>$str</pre>
    $logHtml
</div>
HTML;

    }

    public static function getSubscribedEvents(): array
    {
        return array(
            'console.error' => 'onConsoleError',
            KernelEvents::EXCEPTION => 'onKernelException'
        );
    }

}