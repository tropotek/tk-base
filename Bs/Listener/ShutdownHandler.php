<?php
namespace Bs\Listener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Tk\Log;
use Tk\Uri;

class ShutdownHandler implements EventSubscriberInterface
{
    protected float $scriptStartTime = 0;


    function __construct()
    {
        $this->scriptStartTime = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
    }

    public function onTerminate(TerminateEvent $event): void
    {
        //if (str_starts_with(Uri::create()->getRelativePath(), '/component/')) return;
        if (!StartupHandler::$SCRIPT_CALLED) return;
        if (StartupHandler::hasParam(StartupHandler::METRICS)) {
            $this->debug(sprintf('Time: %s sec    Peek Mem: %s',
                round($this->scriptDuration(), 4),
                \Tk\FileUtil::bytes2String(memory_get_peak_usage(), 4)
            ));
        }
    }

    private function debug(string $str): void
    {
        Log::debug($str);
    }

    /**
     * Get the current script running time in seconds
     */
    protected function scriptDuration(): float
    {
        return microtime(true) - $this->scriptStartTime;
    }

    public static function getSubscribedEvents(): array
    {
        return array(KernelEvents::TERMINATE => 'onTerminate');
    }

}