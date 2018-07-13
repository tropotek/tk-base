<?php
namespace Bs;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2016 Michael Mifsud
 */
class Dispatch
{
    /**
     * @var \Tk\Event\Dispatcher
     */
    protected $dispatcher = null;


    /**
     * @param \Tk\Event\Dispatcher $dispatcher
     * @throws \Tk\Db\Exception
     * @throws \Tk\Exception
     */
    public function __construct($dispatcher)
    {
        $this->dispatcher = $dispatcher;
        $this->init();
    }

    /**
     * @param \Tk\Event\Dispatcher $dispatcher
     * @return Dispatch
     * @throws \Tk\Db\Exception
     * @throws \Tk\Exception
     */
    public static function create($dispatcher)
    {
        $obj = new static($dispatcher);
        return $obj;
    }

    /**
     * @return \Tk\Event\Dispatcher
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * @throws \Tk\Db\Exception
     * @throws \Tk\Exception
     */
    public function init()
    {

        $config = \Bs\Config::getInstance();
        $logger = $config->getLog();
        $request = $config->getRequest();
        $dispatcher = $this->getDispatcher();
        // TODO: Maybe we no longer need the cli check, have a look
        if (!$config->isCli()) {
            $matcher = new \Tk\Routing\UrlMatcher($config->getRouteCollection());
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
        $dispatcher->addSubscriber(new \Bs\Listener\ActionPanelHandler());
        $dispatcher->addSubscriber(new \Bs\Listener\MailHandler());

        if ($config->getAuthHandler())
            $dispatcher->addSubscriber($config->getAuthHandler());
        if ($config->getMasqueradeHandler())
            $dispatcher->addSubscriber($config->getMasqueradeHandler());
        if ($config->getPageTemplateHandler())
            $dispatcher->addSubscriber($config->getPageTemplateHandler());
        if ($config->getCrumbsHandler())
            $dispatcher->addSubscriber($config->getCrumbsHandler());

    }

}