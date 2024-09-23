<?php
namespace Bs\Listener;

use Bs\Registry;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Tk\Config;
use Tk\Log;
use Tk\System;

class StartupHandler implements EventSubscriberInterface
{

    const SITE_NAME             = 0x1;
    const REQUEST_URI           = 0x2;
    const CLIENT_IP             = 0x4;
    const CLIENT_AGENT          = 0x8;
    const SESSION_ID            = 0x10;
    const PHP_VER               = 0x20;
    const CONTROLLER            = 0x40;
    const METRICS               = 0x80;

    const LOG_MIN = 0;

    const LOG_ALL =
        self::SITE_NAME |
        self::REQUEST_URI |
        self::CLIENT_IP |
        self::CLIENT_AGENT |
        self::SESSION_ID |
        self::PHP_VER |
        self::CONTROLLER |
        self::METRICS
    ;

    public static int $PARAMS = 0;

    public static bool $SCRIPT_CALLED = false;


    public static function hasParam(int $flag): bool
    {
        return (self::$PARAMS & $flag) != 0;
    }

    public function onInit(RequestEvent $event)
    {
        $this->init($event->getRequest());
    }

    public function onCommand(ConsoleCommandEvent $event)
    {
        $this->init();
    }

    private function init(?Request $request = null)
    {
        self::$SCRIPT_CALLED = true;

        if (self::$PARAMS) {
            $this->debug('');
        }

        if(self::hasParam(self::SITE_NAME)) {
            $siteName = Registry::instance()?->getSiteName() ?? implode(' ', $_SERVER['argv']);
            if (System::getComposerJson()) {
                $siteName .= sprintf(' [%s]', System::getComposerJson()['name']);
            }
            if (System::getVersion()) {
                $siteName .= sprintf(' [v%s]', System::getVersion());
            }
            if (Config::isDev()) {
                $siteName .= ' {Dev}';
            }
            $this->debug('- Project: ' . trim($siteName));
        }

        if ($request) {
            if (self::hasParam(self::REQUEST_URI)) {
                $this->debug(sprintf('- Request: [%s][%s] %s%s%s%s',
                    $request->getMethod(),
                    http_response_code(),
                    $request->getScheme() . '://' . $request->getHost(),
                    $request->getBaseUrl(),
                    $request->getPathInfo(),
                    (empty($_SERVER['QUERY_STRING'] ?? '')) ? '' : '?' . ($_SERVER['QUERY_STRING'])
                ));
            }

            if (self::hasParam(self::CLIENT_IP)) {
                $this->debug('- Client IP: ' . $request->getClientIp());
            }

            if (self::hasParam(self::CLIENT_AGENT)) {
                $this->debug('- Agent: ' . $request->headers->get('User-Agent'));
            }
            if (self::hasParam(self::SESSION_ID) && $request->getSession()) {
                $this->debug(sprintf('- Session: %s [ID: %s]', $request->getSession()->getName(), $request->getSession()->getId()));
            }
        } else {
            if (self::hasParam(self::REQUEST_URI)) {
                $this->debug('- CLI: ' . implode(' ', $_SERVER['argv']));
                $this->debug('- Path: ' . Config::getBasePath());
            }
        }

        if (self::hasParam(self::PHP_VER)) {
            $this->debug('- PHP: ' . \PHP_VERSION);
        }
    }

    public function onRequest(RequestEvent $event)
    {
        if ($event->getRequest()->attributes->has('_route')) {
            if (!self::hasParam(self::PHP_VER)) return;
            $controller = $event->getRequest()->attributes->get('_controller');
            if (is_array($controller)) {
                $controller = implode('::', $controller);
            }
            if (is_string($controller)) {
                $this->debug('- Controller: ' . $controller);
            } else {
                $this->debug('- Controller: {unknown}');
            }
        }
    }

    private function debug(string $str)
    {
        Log::debug($str);
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [['onInit', 255], ['onRequest']],
            ConsoleEvents::COMMAND  => 'onCommand'
        ];
    }

}