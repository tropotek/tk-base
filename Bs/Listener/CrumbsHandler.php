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
class CrumbsHandler extends \Tk\Listener\CrumbsHandler
{
    /**
     * Init the crumbs for this app
     *
     * @param GetResponseEvent $event
     */
    public function onSystemInit(GetResponseEvent $event)
    {
        $config = \Bs\Config::getInstance();
        $user = $config->getUser();

        $homeTitle = '';
        $homeUrl = '';
        if ($user) {
            $homeTitle = 'Dashboard';
            $homeUrl = $config->getHomeUrl($user)->getRelativePath();
        }
        $config->getCrumbs($homeTitle, $homeUrl);
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array_merge(parent::getSubscribedEvents(),
            array(
                KernelEvents::REQUEST => array('onSystemInit', -1)
            )
        );
    }

}