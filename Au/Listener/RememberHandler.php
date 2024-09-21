<?php
namespace Au\Listener;

use Au\Auth;
use Au\Remember;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Tk\Log;
use Tk\Uri;

class RememberHandler implements EventSubscriberInterface
{

    public function onRequest(RequestEvent $event): void
    {
        // Check user still logged in, if not use any remember me cookies to auto login and redirect to back to this URI
        if (!Auth::getAuthUser()) {
            $auth = Remember::retrieveMe();
            if ($auth) {
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