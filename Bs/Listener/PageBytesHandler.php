<?php
namespace Bs\Listener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Dom\Modifier\PageBytes;
use Tk\Log;
use Bs\Mvc\EventListener\StartupHandler;

class PageBytesHandler implements EventSubscriberInterface
{
    protected PageBytes $pageBytes;


    function __construct(PageBytes $pageBytes)
    {
        $this->pageBytes = $pageBytes;
    }

    public function onTerminate(TerminateEvent $event)
    {
        if (!StartupHandler::$SCRIPT_CALLED) return;
        if (StartupHandler::hasParam(StartupHandler::METRICS)) {
            foreach (explode("\n", $this->pageBytesToString()) as $line) {
                Log::debug($line);
            }
        }
    }

    private function pageBytesToString(): string
    {
        $str = '';
        $j = $this->pageBytes->getJsBytes();
        $c = $this->pageBytes->getCssBytes();
        $h = $this->pageBytes->getHtmlBytes();
        $t = $j + $c +$h;

        if ($t > 0) {
            $str .= 'Page Sizes:' . \PHP_EOL;
            $str .= sprintf('  JS:      %6s', \Tk\FileUtil::bytes2String($j)) . \PHP_EOL;
            $str .= sprintf('  CSS:     %6s', \Tk\FileUtil::bytes2String($c)) . \PHP_EOL;
            $str .= sprintf('  HTML:    %6s', \Tk\FileUtil::bytes2String($h)) . \PHP_EOL;
            $str .= sprintf('  TOTAL:   %6s', \Tk\FileUtil::bytes2String($t));
        }
        return $str;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::TERMINATE => ['onTerminate', -100]
        ];
    }

}