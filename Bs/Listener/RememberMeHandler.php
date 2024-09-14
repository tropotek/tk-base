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

    public function onRequest(RequestEvent $event)
    {
        // TODO: Check user still logged in, if not use any remember me cookies to auto login and redirect to back to this URI
        if (!Factory::instance()->getAuthUser()) {
            $user = \Bs\Db\User::retrieveMe();
            if ($user) {
                Log::alert('user `' . $user->username . '` auto logged in via cookie');
                Uri::create()->redirect();
            }
        }


        // TODO: Check if maintenance mode is enabled then redirect to appropriate URI

        //\Tk\Log::emergency('TODO: HTTP implement RequestHandler....');

    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'onRequest',
        ];
    }

}