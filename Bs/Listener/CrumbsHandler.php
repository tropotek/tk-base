<?php
namespace Bs\Listener;

use Tk\Event\Subscriber;
use Tk\Kernel\KernelEvents;
use Tk\Event\GetResponseEvent;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class CrumbsHandler implements Subscriber
{
    /**
     * do any auth init setup
     *
     * @param GetResponseEvent $event
     */
    public function onSystemInit(GetResponseEvent $event)
    {
        $config = \Bs\Config::getInstance();
        $user = $config->getUser();
        if ($user) {
            \Tk\Ui\Crumbs::$SID = 'crumbs.manager.'.$user->role;
            \Tk\Ui\Crumbs::$homeTitle = 'Dashboard';
            \Tk\Ui\Crumbs::$homeUrl = $user->getHomeUrl();
        }
        // Create New Instance
        \Tk\Ui\Crumbs::getInstance();

    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => array('onSystemInit', -1)
        );
    }

}