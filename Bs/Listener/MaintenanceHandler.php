<?php
namespace Bs\Listener;

use Au\Auth;
use Bs\Controller\Maintenance;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Bs\Registry;

class MaintenanceHandler implements EventSubscriberInterface
{

    public function onController(ControllerEvent $event)
    {
        $controller = $event->getController();

        if (!is_array($controller)) return;
        $class = get_class($controller[0]);

        // Allow admin users access
        if (Auth::getAuthUser()?->hasPermission(Auth::PERM_ADMIN)) {
            return;
        }

        // Exit if not in maintenance mode
        if (
            !Registry::instance()->get('system.maintenance.enabled') ||
            $controller[0] instanceof Maintenance
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

        $c = new Maintenance();
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