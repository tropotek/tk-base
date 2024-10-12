<?php
namespace Bs\Listener;

use Bs\Auth;
use Bs\Db\GuestToken;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Tk\Alert;
use Tk\Log;
use Tk\Uri;

class GuestHandler implements EventSubscriberInterface
{

    protected ?GuestToken $gt = null;


    public function onRequest(RequestEvent $event): void
    {
        // ignore error pages
        if($event->getRequest()->attributes->get('e') instanceof \Exception) return;

        // Init new guest access and save token to session
        if (isset($_GET[GuestToken::TOKEN_RID])) {
            $token = trim($_GET[GuestToken::TOKEN_RID]);
            $this->gt = GuestToken::find($token);

            if (is_null($this->gt)) Log::error("Invalid guest token {$token}");

            if ($this->gt && count($this->gt->pages) == 0) Log::error("no pages available for token {$token}");

            if (is_null($this->gt) || count($this->gt->pages) == 0 || $_SERVER['REQUEST_METHOD'] == "HEAD") {
                Alert::addError("The link you followed is invalid or expired. Check the link and try again.");
                return;
            }
            Auth::logout();

            $_SESSION[GuestToken::TOKEN_SID] = $token;

            return;
        }

        // validate the page if token exists is session
        $gt = GuestToken::getSessionToken();
        if ($gt instanceof GuestToken) {
            // check the requested page is a valid token page
            if (!$gt->hasUrl(Uri::create())) {
                //throw new Exception("The link you followed is invalid or expired. Check the link and try again");
                unset($_SESSION[GuestToken::TOKEN_SID]);
                Alert::addError("The link you followed is invalid or expired. Check the link and try again.");
                Uri::create('/')->redirect();
            }
        }

    }

    public function onView(ViewEvent $event): void
    {
        // ignore error pages
        if($event->getRequest()->attributes->get('e') instanceof \Exception) return;
        if (is_null($this->gt)) return; // is not new guest session

        // redirect to the first page in the pages list with Javascript
        // to block bots and preview requests, only if this is a new guest session
        $page = array_shift($this->gt->pages);
        $page = json_encode('/'.trim($page, '/'));

        $html = <<<HTML
            <noscript>
                <h3 class="text-danger">Something went wrong</h3>
                <h4>This page requires Javascript enabled in your web browser.</h4>
                <p>Enable Javascript and click the link again.</p>
            </noscript>
            <script>
                const page = {$page};
                if (page) {
                    location.replace(page);
                }
            </script>
        HTML;

        $event->setResponse(new Response($html));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onRequest',
            KernelEvents::VIEW => ['onView', 100],
        ];
    }

}