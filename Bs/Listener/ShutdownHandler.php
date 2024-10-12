<?php
namespace Bs\Listener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Tk\Log;

class ShutdownHandler implements EventSubscriberInterface
{
    protected float $scriptStartTime = 0;


    function __construct(float $scriptStartTime = 0)
    {
        $this->scriptStartTime = $scriptStartTime;
    }

    public function onTerminate(TerminateEvent $event): void
    {
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