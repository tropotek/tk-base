<?php
namespace Bs\Listener;

use Bs\Auth;
use Bs\Db\Remember;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Tk\Log;
use Tk\Uri;

class RememberHandler implements EventSubscriberInterface
{
    protected ?Uri $homeUrl = null;

    public function __construct(?Uri $homeUrl = null)
    {
        $this->homeUrl = $homeUrl;
    }

    public function onRequest(RequestEvent $event): void
    {
        // Check user still logged in, if not use any remember me cookies to auto login and redirect to back to this URI
        if (!Auth::getAuthUser()) {
            $auth = Remember::retrieveMe();
            if ($auth) {
                Log::debug('Auth Remember Handler: user auto logged in via cookie');
                if ($this->homeUrl) {
                    $this->homeUrl->redirect();
                }
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