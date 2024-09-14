<?php
namespace Bs\Mvc;

use Bs\Factory;
use Bs\Listener\ContentLength;
use Bs\Listener\LogExceptionListener;
use Bs\Listener\ViewHandler;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\EventListener\ResponseListener;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Tk\Config;
use Bs\Listener\ShutdownHandler;
use Bs\Listener\StartupHandler;
use Tk\System;

/**
 * This object sets up the EventDispatcher and
 * attaches all the listeners required for your application.
 *
 * Subclass this object in your App (to setup a Tk framework) and then override the Factory method
 * Factory::initDispatcher()
 */
class Dispatch
{

    protected ?EventDispatcherInterface $dispatcher = null;


    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
        $this->init();
    }

    private function init(): void
    {
        $this->commonInit();
        if (System::isCli()) {
            $this->cliInit();
        } else {
            $this->httpInit();
        }
    }

    /**
     * Any Common listeners that are used in both HTTPS or CLI requests
     */
    protected function commonInit(): void
    {
        if (Config::instance()->isDev()) {
            $this->getDispatcher()->addSubscriber(new StartupHandler());
            $this->getDispatcher()->addSubscriber(new ShutdownHandler(Config::instance()->get('script.start.time')));
        }
    }

    /**
     * Called this when executing http requests
     */
    protected function httpInit(): void
    {
        $this->getDispatcher()->addSubscriber(new RouterListener(
            Factory::instance()->getRouteMatcher(),
            Factory::instance()->getRequestStack()
        ));

        $this->getDispatcher()->addSubscriber(new LogExceptionListener(
            Config::instance()->isDebug()
        ));

        $this->getDispatcher()->addSubscriber(new ViewHandler());
        $this->getDispatcher()->addSubscriber(new ResponseListener('UTF-8'));
        $this->getDispatcher()->addSubscriber(new ContentLength());

    }

    /**
     * Called this when executing Console/CLI requests
     */
    protected function cliInit(): void
    {
    }

    public function getDispatcher(): ?EventDispatcherInterface
    {
        return $this->dispatcher;
    }
}