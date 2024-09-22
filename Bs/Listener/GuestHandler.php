<?php
namespace Bs\Listener;

use Au\Auth;
use Bs\Db\GuestToken;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Tk\Alert;
use Tk\Exception;
use Tk\Log;
use Tk\Uri;

class GuestHandler implements EventSubscriberInterface
{

    protected ?GuestToken $gt = null;


    public function onRequest(RequestEvent $event)
    {
        // validate the page if token exists is session
        $gt = GuestToken::getSessionToken();
        if (!is_null($gt) && !isset($_GET[GuestToken::TOKEN_RID])) {
            // check the requested page is a valid token page
            if (!$gt->hasUrl(Uri::create())) {
                //throw new Exception("The link you followed is invalid. Please check the link and try again.");
                Alert::addError("The link you followed is invalid. Please check the link and try again.");
                unset($_SESSION[GuestToken::TOKEN_SID]);
                Uri::create('/')->redirect();
            }
            return;
        }

        // access page using token if token string exists
        if (!isset($_GET[GuestToken::TOKEN_RID])) return;

        $token = trim($_GET[GuestToken::TOKEN_RID]);
        $this->gt = GuestToken::find($token);

        if (is_null($this->gt)) Log::error("Invalid guest token {$token}");

        if ($this->gt && count($this->gt->pages) == 0) Log::error("no pages available for token {$token}");

        if (is_null($this->gt) || count($this->gt->pages) == 0 || $_SERVER['REQUEST_METHOD'] == "HEAD") {
            Alert::addError("The link you followed is invalid. Please check the link and try again.");
            return;
        }
        Auth::logout();

        $_SESSION[GuestToken::TOKEN_SID] = $token;

    }

    public function onView(ViewEvent $event)
    {
        if (is_null($this->gt)) return;

        // redirect to the first page in the pages list with Javascript
        // to block bots and preview requests
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

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'onRequest',
            KernelEvents::VIEW => ['onView', 100],
        ];
    }

}