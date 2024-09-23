<?php
namespace Bs\Listener;

use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Tk\Log;

class LogExceptionListener implements EventSubscriberInterface
{

    protected bool $debug = false;


    public function __construct(bool $debug = false)
    {
        $this->debug = $debug;
    }

    public function onException(ExceptionEvent $event)
    {
        $this->logException($event->getThrowable());
    }

    public function onConsoleError(ConsoleErrorEvent $event)
    {
        $this->logException($event->getError());
    }

    protected function logException(\Throwable $e)
    {
        if ($e instanceof ResourceNotFoundException || $e instanceof NotFoundHttpException) {
            Log::error(self::getCallerLine($e) . $e->getMessage());
        } else {
            if ($this->debug) {
                if ($e instanceof \HttpException) {
                    Log::info(self::getCallerLine($e) . $e->__toString());
                } elseif ($e instanceof \Tk\WarningException) {
                    Log::warning(self::getCallerLine($e) . $e->__toString());
                } else {
                    Log::error(self::getCallerLine($e) . $e->__toString());
                }
            } else {
                if ($e instanceof \HttpException) {
                    Log::info(self::getCallerLine($e) . $e->getMessage());
                } elseif ($e instanceof \Tk\WarningException) {
                    Log::warning(self::getCallerLine($e) . $e->getMessage());
                } else {
                    Log::error(self::getCallerLine($e) . $e->getMessage());
                }
            }
        }
    }

    private static function getCallerLine(\Throwable $e): string
    {
        $line = $e->getLine();
        $file = str_replace(\Tk\Config::getBasePath(), '', $e->getFile());
        return sprintf('[%s:%s] ', $file, $line);
    }

    public static function getSubscribedEvents()
    {
        return array(
            'console.error' => 'onConsoleError',
            KernelEvents::EXCEPTION => 'onException'
        );
    }

}