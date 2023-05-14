<?php
namespace Bs;

use Dom\Mvc\EventListener\PageBytesHandler;
use Dom\Mvc\EventListener\ViewHandler;
use Dom\Mvc\Modifier\PageBytes;
use Tk\Mvc\EventListener\ExceptionListener;

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

        $this->getDispatcher()->addSubscriber(new ExceptionListener(
            'Bs\Controller\Error::doDefault',
            $this->getConfig()->isDebug()
        ));

        // Handle DomTemplate renders and templates in controller returns
        $this->getDispatcher()->addSubscriber(new ViewHandler($this->getFactory()->getTemplateModifier()));

        // Show total page bytes
        /** @var PageBytes $pageBytes */
        $pageBytes = $this->getFactory()->getTemplateModifier()->getFilter('pageBytes');
        $this->getDispatcher()->addSubscriber(new PageBytesHandler(
            $this->getFactory()->getLogger(),
            $pageBytes
        ));

    }

    /**
     * Called this when executing Console/CLI requests
     */
    protected function cliInit()
    {
        parent::cliInit();


    }

}