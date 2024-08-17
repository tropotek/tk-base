<?php
namespace Bs;

use Bs\Listener\MaintenanceHandler;
use Bs\Listener\PageHandler;
use Bs\Listener\RememberMeHandler;
use Bs\Listener\PageBytesHandler;
use Bs\Listener\DomViewHandler;
use Dom\Modifier\PageBytes;
use Tk\Mvc\EventListener\ExceptionListener;
use Bs\Listener\CrumbsHandler;

class Dispatch extends \Tk\Mvc\Dispatch
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
            $this->getConfig()->isDebug()
        ));

        // render the page template with controller HTML content if enabled/exists
        $this->getDispatcher()->addSubscriber(new PageHandler());

        // renders DomTemplates from controller returns if page template disabled or not exists
        $this->getDispatcher()->addSubscriber(new DomViewHandler($this->getFactory()->getTemplateModifier()));

        // Show total page bytes
        /** @var PageBytes $pageBytes */
        $pageBytes = $this->getFactory()->getTemplateModifier()->getFilter('pageBytes');
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