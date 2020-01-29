<?php
namespace Bs\Listener;

use Symfony\Component\HttpKernel\KernelEvents;

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
     * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
     */
    public function onSystemInit($event)
    {
        $config = \Bs\Config::getInstance();
        $user = $config->getAuthUser();
        $homeTitle = '';
        $homeUrl = '';
        if ($user) {
            $homeTitle = 'Dashboard';
            $homeUrl = $config->getUserHomeUrl($user)->getRelativePath();
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