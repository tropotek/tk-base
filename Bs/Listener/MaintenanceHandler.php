<?php
namespace Bs\Listener;

use Bs\Db\Permissions;
use Bs\Factory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Bs\Registry;

class MaintenanceHandler implements EventSubscriberInterface
{

    public function onController(\Symfony\Component\HttpKernel\Event\ControllerEvent $event)
    {
        $controller = $event->getController();

        if (!is_array($controller)) return;
        $class = get_class($controller[0]);

        // Allow admin users access
        if (Factory::instance()->getAuthUser() && Factory::instance()->getAuthUser()->hasPermission(Permissions::PERM_ADMIN)) {
            return;
        }

        // Exit if not in maintenance mode
        if (
            !Registry::instance()->get('system.maintenance.enabled') ||
            $controller[0] instanceof \Bs\Controller\Maintenance
        ) {
            return;
        }
        $method = 'doDefault';

        // check if the controller is an API controller (return JSON response)
        if (str_contains($class, '\\Api\\')) {
            $method = 'doApi';
        }

        // TODO See if we need this implemented
//        if ($this->getConfig()->get('path.template.'.Page::TEMPLATE_MAINTENANCE)) {
//            $event->getRequest()->attributes->set('template', Page::TEMPLATE_MAINTENANCE);
//            $params = $event->getRequest()->attributes->get('_route_params');
//            $params['template'] = Page::TEMPLATE_MAINTENANCE;
//            $event->getRequest()->attributes->set('_route_params', $params);
//        }

        $c = new \Bs\Controller\Maintenance();
        $event->setController([$c, $method]);
    }

    /**
     * Use this to pragmatically enable/disable maintenance
     */
    public static function enableMaintenanceMode(bool $b = true, string $message = '')
    {
        $data = Registry::instance();
        if ($b) {
            $data->set('system.maintenance.enabled', 'system.maintenance.enabled');
            if ($message)
                $data->set('system.maintenance.message', $message);
        } else {
            $data->set('system.maintenance.enabled', '');
        }
        $data->save();
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER =>  ['onController', 0],
        ];
    }
}