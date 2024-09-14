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


class Dispatch extends \Bs\Mvc\Dispatch
{

    /**
     * Any Common listeners that are used in both HTTPS or CLI requests
     */
    protected function commonInit()
    {
        parent::commonInit();
    }

    /**
     * Called this when executing http requests
     */
    protected function httpInit()
    {
        parent::httpInit();

        $this->getDispatcher()->addSubscriber(new MaintenanceHandler());
        $this->getDispatcher()->addSubscriber(new RememberMeHandler());

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
        $this->getDispatcher()->addSubscriber(new CrumbsHandler());

    }

    /**
     * Called this when executing Console/CLI requests
     */
    protected function cliInit()
    {
        parent::cliInit();
    }

}