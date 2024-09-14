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
use Tk\Log;


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
     *  TODO: log all errors and send a compiled message periodically (IE: daily, weekly, monthly)
     *        This would stop mass emails on major system failures and DOS attacks...
     * @param Throwable $e
     */
    protected function emailException(Throwable $e): void
    {
        // These errors are not required they can cause email loops
        if ($e instanceof ResourceNotFoundException ||
            $e instanceof NotFoundHttpException ||
            $e instanceof MethodNotAllowedHttpException)
            return;

        // Stop console instance exists email errors they are not needed
        //if ($e->getCode() == Console::ERROR_CODE_INSTANCE_EXISTS) return; ??

        try {
            if (count($this->emailList)) {
                foreach ($this->emailList as $email) {
                    $message = Factory::instance()->createMessage();
                    $message->setFrom($email);
                    $message->addTo($email);
                    $subject = "{$this->siteTitle} Error: '{$e->getMessage()}'";
                    $message->setSubject($subject);
                    $message->setContent($this->getExceptionHtml($e));
                    $message->addHeader('X-Exception', get_class($e));
                    $message->set('sig', '');
                    Factory::instance()->getMailGateway()->send($message);
                }
            }
        } catch (Exception $ee) { Log::warning($ee->__toString()); }
    }

    public function getExceptionHtml(Throwable $e, bool $withTrace = true): string
    {
        $class = get_class($e);
        $msg = $e->getMessage();
        $str = '';
        $extra = '';
        $logHtml = '';

        if ($withTrace) {
            $toString = trim($e->__toString());
            $str = str_replace(["&lt;?php&nbsp;<br />", 'color: #FF8000'], ['', 'color: #666'],
                highlight_string("<?php \n" . $toString, true));
            $extra = sprintf('<br/>in <em>%s:%s</em>',  $e->getFile(), $e->getLine());
        }

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
    <pre style="">$str</pre>
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