<?php
namespace Bs\Listener;

use Tk\Event\Subscriber;
use Tk\Kernel\KernelEvents;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class MaintenanceHandler implements Subscriber
{

    // TODO:
    // TODO:
    // TODO:   We need this to render and display the page so the URL remains the same
    // TODO:
    // TODO:   Then the user can just reload the page when the site is running again....>>>
    // TODO:
    // TODO:
    // TODO:
    // TODO:
    // TODO:
    // TODO:
    // TODO:





    /**
     * kernel.controller
     * @param \Tk\Event\ControllerEvent $event
     * @throws \Exception
     */
    public function onController(\Tk\Event\ControllerEvent $event)
    {
        /** @var \Tk\Controller\Iface $controller */
        $controller = $event->getController();
        if (!$controller instanceof \Bs\Controller\Login && !$controller instanceof \Bs\Controller\Logout && !$controller instanceof \Bs\Controller\Maintenance && $this->getConfig()->get('site.maintenance.enabled')) {
            if ($this->getConfig()->getUser()) {
                if ($this->getConfig()->getUser()->hasPermission(\Bs\Db\Permission::TYPE_ADMIN)) return;
                if ($this->getConfig()->getMasqueradeHandler()->getMasqueradingUser() && $this->getConfig()->getMasqueradeHandler()->getMasqueradingUser()->hasPermission(\Bs\Db\Permission::TYPE_ADMIN)) return;
            }
            \Tk\Uri::create('/maintenance.html')->redirect();
        }

    }

    /**
     * @param \Tk\Event\Event $event
     * @throws \Exception
     */
    public function showPage(\Tk\Event\Event $event)
    {
        if (!$this->getConfig()->get('site.maintenance.enabled')) return;
        $controller = $event->get('controller');
        if ($controller instanceof \Bs\Controller\Iface && !$controller instanceof \Bs\Controller\Maintenance) {
            $page = $controller->getPage();
            if (!$page) return;
            $template = $page->getTemplate();

            $html = <<<HTML
<div class="tk-ribbon tk-ribbon-danger" style="z-index: 99999"><span>Maintenance</span></div>
HTML;
            $template->prependHtml($template->getBodyElement(), $html);
            $template->addCss($template->getBodyElement() ,'tk-ribbon-box');
        }

    }

    /**
     * Use this to pragmatically enable/disable maintenance
     *
     * @param bool $b
     * @param string $message
     * @throws \Tk\Db\Exception
     */
    public static function enableMaintenanceMode($b = true, $message = '')
    {
        $data = \Tk\Db\Data::create();
        if ($b) {
            $data->set('site.maintenance.enabled', 'site.maintenance.enabled');
            if ($message)
                $data->set('site.maintenance.message', $message);
        } else {
            $data->set('site.maintenance.enabled', '');
        }
        $data->save();
    }




    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::CONTROLLER =>  array('onController', 1),
            \Tk\PageEvents::CONTROLLER_SHOW => 'showPage'
        );
    }

    /**
     * @return \App\Config|\Tk\Config
     */
    public function getConfig()
    {
        return \App\Config::getInstance();
    }

}