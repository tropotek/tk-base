<?php
namespace Bs\Listener;

use Bs\Auth;
use Bs\Controller\Maintenance;
use Bs\Mvc\ComponentInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Bs\Registry;

class MaintenanceHandler implements EventSubscriberInterface
{

    public function onController(ControllerEvent $event): void
    {
        if (!Registry::isMaintenanceMode()) return;

        // Allow admin users access
        if (Auth::getAuthUser()?->hasPermission(Auth::PERM_ADMIN)) {
            return;
        }

        $controller = $event->getController();
        if (!is_array($controller)) return;

        // exit for component of maintenance controllers
        if (
            $controller[0] instanceof ComponentInterface ||
            $controller[0] instanceof Maintenance
        ) {
            return;
        }

        $method = 'doDefault';

        // check if the controller is an API controller (return JSON response)
        $class = get_class($controller[0]);
        if (str_contains($class, '\\Api\\')) {
            $method = 'doApi';
        }

        $c = new Maintenance();
        $event->setController([$c, $method]);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER =>  ['onController', 1],
        ];
    }
}