<?php
namespace Bs\Listener;

use Bs\Factory;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Tk\Log;
use Tk\Uri;

class RememberMeHandler implements EventSubscriberInterface
{

    public function onRequest(RequestEvent $event): void
    {
        // Check user still logged in, if not use any remember me cookies to auto login and redirect to back to this URI
        if (!Factory::instance()->getAuthUser()) {
            $user = \Bs\Db\User::retrieveMe();
            if ($user) {
                Log::info('user auto logged in via cookie');
                Uri::create()->redirect();
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'onRequest',
        ];
    }

}