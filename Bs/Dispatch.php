<?php
namespace Bs;

use Bs\Listener\MaintenanceHandler;
use Bs\Listener\PageHandler;
use Bs\Listener\RememberMeHandler;
use Bs\Listener\PageBytesHandler;
use Bs\Listener\DomViewHandler;
use Bs\Listener\ExceptionEmailListener;
use Dom\Modifier\PageBytes;
use Tk\Config;
use Bs\Listener\ExceptionListener;
use Bs\Listener\CrumbsHandler;
use Bs\Factory;
use Bs\Listener\ContentLength;
use Bs\Listener\LogExceptionListener;
use Bs\Listener\ViewHandler;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\EventListener\ResponseListener;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
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

        $this->getDispatcher()->addSubscriber(new ExceptionListener(
            'Bs\Controller\Error::doDefault',
            Config::instance()->isDebug()
        ));

        if (Config::instance()->isProd()) {
            $this->getDispatcher()->addSubscriber(new ExceptionEmailListener(
                Config::instance()->get('system.email.exception', []),
                Registry::instance()->get('site.name')
            ));
        }

        // render the page template with controller HTML content if enabled/exists
        $this->getDispatcher()->addSubscriber(new PageHandler());

        // renders DomTemplates from controller returns if page template disabled or not exists
        $this->getDispatcher()->addSubscriber(new DomViewHandler(Factory::instance()->getTemplateModifier()));

        // Show total page bytes
        /** @var PageBytes $pageBytes */
        $pageBytes = Factory::instance()->getTemplateModifier()->getFilter('pageBytes');
        if ($pageBytes) {
            $this->getDispatcher()->addSubscriber(new PageBytesHandler($pageBytes));
        }

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