<?php
namespace Bs;

use Tk\Event\Dispatcher;
use Tk\Controller\Resolver;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2016 Michael Mifsud
 */
class FrontController extends \Tk\Kernel\HttpKernel
{

    /**
     * @param Dispatcher $dispatcher
     * @param Resolver $resolver
     * @throws \Tk\Exception
     */
    public function __construct(Dispatcher $dispatcher, Resolver $resolver)
    {
        parent::__construct($dispatcher, $resolver);

        $config = \Bs\Config::getInstance();

        // Init the plugins
        $config->getPluginFactory();

        // Initiate the email gateway
        $config->getEmailGateway();

        $this->init();
    }

    /**
     * init Application front controller
     *
     * @throws \Tk\Exception
     */
    public function init()
    {
        $config = \Bs\Config::getInstance();
        $logger = $config->getLog();
        $request = $config->getRequest();
        $dispatcher = $this->getDispatcher();

        if (!$config->isCli()) {
            $matcher = new \Tk\Routing\UrlMatcher($config->get('site.routes'));
            $dispatcher->addSubscriber(new \Tk\Listener\RouteListener($matcher));
            $dispatcher->addSubscriber(new \Tk\Listener\PageHandler($dispatcher));
            $dispatcher->addSubscriber(new \Tk\Listener\ResponseHandler($config->getDomModifier()));
        }

        // Tk Listeners
        $dispatcher->addSubscriber(new \Tk\Listener\StartupHandler($logger, $request, $config->getSession()));


        // Exception Handling, log first so we can grab the session log
        $dispatcher->addSubscriber(new \Tk\Listener\LogExceptionListener($logger, true));

        if (preg_match('|^/ajax/.+|', $request->getUri()->getRelativePath())) { // If ajax request
            $dispatcher->addSubscriber(new \Tk\Listener\JsonExceptionListener($config->isDebug()));
        } else {
            $dispatcher->addSubscriber(new \Tk\Listener\ExceptionListener($config->isDebug(), 'Bs\Controller\Error'));
        }

        if ($config->get('system.email.exception')) {
            $dispatcher->addSubscriber(new \Tk\Listener\ExceptionEmailListener(
                $config->getEmailGateway(),
                $config->get('system.email.exception'),
                $config->get('site.title')
            ));
        }


        $sh = new \Tk\Listener\ShutdownHandler($logger, $config->getScriptTime());
        $sh->setPageBytes($config->getDomFilterPageBytes());
        $dispatcher->addSubscriber($sh);

        // App Listeners
        $dispatcher->addSubscriber(new \Bs\Listener\CrumbsHandler());
        $dispatcher->addSubscriber(new \Tk\Listener\CrumbsHandler());

        $dispatcher->addSubscriber(new \Bs\Listener\AuthHandler());
        $dispatcher->addSubscriber(new \Bs\Listener\MasqueradeHandler());
        $dispatcher->addSubscriber(new \Bs\Listener\ActionPanelHandler());
        $dispatcher->addSubscriber(new \Bs\Listener\PageTemplateHandler());
        $dispatcher->addSubscriber(new \Bs\Listener\MailHandler());

    }

}