<?php
namespace Bs;


use Bs\Listener\InstallHandler;
use Tk\ConfigTrait;
use Bs\Listener\MailHandler;
use Bs\Listener\MaintenanceHandler;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing;
use Symfony\Component\HttpKernel;
use Symfony\Component\HttpFoundation\RequestStack;
use Tk\Listener\ActionPanelHandler;
use Tk\Listener\ExceptionEmailListener;
use Tk\Listener\ExceptionListener;
use Tk\Listener\JsonExceptionListener;
use Tk\Listener\LogExceptionListener;
use Tk\Listener\NotFoundLogListener;
use Tk\Listener\PageHandler;
use Tk\Listener\ResponseHandler;
use Tk\Listener\ShutdownHandler;
use Tk\Listener\StartupHandler;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2016 Michael Mifsud
 */
class Dispatch
{
    use ConfigTrait;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher = null;


    /**
     * @param  EventDispatcherInterface $dispatcher
     */
    public function __construct($dispatcher)
    {
        $this->dispatcher = $dispatcher;
        $this->init();
    }

    /**
     * @param  EventDispatcherInterface $dispatcher
     * @return Dispatch
     */
    public static function create($dispatcher)
    {
        $obj = new static($dispatcher);
        return $obj;
    }

    /**
     * @return  EventDispatcherInterface
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     */
    public function initObjects()
    {
        // Init the plugins
        $this->getConfig()->getPluginFactory();
        // Initiate the email gateway
        $this->getConfig()->getEmailGateway();
        // Initiate the Dom\Template loader object
        $this->getConfig()->getDomLoader();

    }

    /**
     * @throws \Exception
     */
    public function init()
    {

        $config = Config::getInstance();
        $logger = $config->getLog();
        $request = $config->getRequest();
        $dispatcher = $this->getDispatcher();

        $this->initObjects();

        // TODO: Maybe we no longer need the cli check, have a look
        if (!$config->isCli()) {

            $context = new Routing\RequestContext();
            $matcher = new Routing\Matcher\UrlMatcher($config->getRouteCollection(), $context);
            $requestStack = new RequestStack();
            $dispatcher->addSubscriber(new HttpKernel\EventListener\RouterListener($matcher, $requestStack));
            //$dispatcher->addSubscriber(new \Tk\Listener\RouteListener($matcher));

            $dispatcher->addSubscriber(new PageHandler($dispatcher));
            $dispatcher->addSubscriber(new ResponseHandler($config->getDomModifier()));
        }

        // Tk Listeners
        $dispatcher->addSubscriber(new StartupHandler($logger, $request, $config->getSession()));

        if ($config->get('system.email.exception')) {
            $dispatcher->addSubscriber(new ExceptionEmailListener(
                $config->getEmailGateway(),
                $config->get('system.email.exception'),
                $config->get('site.title')
            ));
        }

        // Exception Handling, log first so we can grab the session log
        $dispatcher->addSubscriber(new LogExceptionListener($logger, true));

        // Log not found URI's for future inspections
        if ($config->getLogPath()) {
            $notFoundLogPath = dirname($config->getLogPath()) . '/404.log';
            if (!is_file($notFoundLogPath)) {
                file_put_contents($notFoundLogPath, '');
            }
            $log = new Logger('notfound');
            $handler = new StreamHandler($notFoundLogPath, Logger::NOTICE);
            $log->pushHandler($handler);
            $dispatcher->addSubscriber(new NotFoundLogListener($log));
        }

        if (preg_match('|^/ajax/.+|', $request->getTkUri()->getRelativePath())) { // If ajax request
            $dispatcher->addSubscriber(new JsonExceptionListener($config->isDebug()));
        } else {
            $dispatcher->addSubscriber(new ExceptionListener($config->isDebug(), 'Bs\Controller\Error'));
        }

        $sh = new ShutdownHandler($logger, $config->getScriptTime());
        $sh->setPageBytes($config->getDomFilterPageBytes());
        $dispatcher->addSubscriber($sh);

        // App Listeners
        $dispatcher->addSubscriber($config->getInstallHandler());
        $dispatcher->addSubscriber(new ActionPanelHandler());
        $dispatcher->addSubscriber(new MailHandler());

        if ($config->getAuthHandler())
            $dispatcher->addSubscriber($config->getAuthHandler());
        if ($config->getMasqueradeHandler())
            $dispatcher->addSubscriber($config->getMasqueradeHandler());
        if ($config->getPageTemplateHandler())
            $dispatcher->addSubscriber($config->getPageTemplateHandler());
        if ($config->getCrumbsHandler())
            $dispatcher->addSubscriber($config->getCrumbsHandler());

        $dispatcher->addSubscriber(new MaintenanceHandler());

    }

}