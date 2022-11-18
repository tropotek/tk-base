<?php
namespace Bs\Controller;

use Tk\Request;
use Tk\Auth\AuthEvents;
use Tk\Event\AuthEvent;

/**
 * @author Michael Mifsud <http://www.tropotek.com/>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Logout extends Iface
{

    /**
     * @param Request $request
     */
    public function doDefault(Request $request)
    {
        $event = new AuthEvent();
        $this->getConfig()->getEventDispatcher()->dispatch(AuthEvents::LOGOUT, $event);
        if ($event->getRedirect())
            $event->getRedirect()->redirect();
    }


    /**
     * @return \Dom\Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<HTML
<div></div>
HTML;

        return \Dom\Loader::load($xhtml);
    }
}