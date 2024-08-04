<?php
namespace Bs\Listener;

use Bs\Ui\Crumbs;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Tk\Traits\SystemTrait;
use Tk\Uri;

class CrumbsHandler implements EventSubscriberInterface
{
    use SystemTrait;

    public function onRequest(RequestEvent $event)
    {
        // Init the crumb stack
        $this->getFactory()->getCrumbs();
    }

    public function onView(ViewEvent $event)
    {
        $page = $this->getFactory()->getPage();
        if (!$page) return;

        if ($page->isEnabled() && $page->isCrumbEnabled()) {
            $crumbs = $page->getCrumbs();
            if (!$crumbs || $event->getRequest()->query->get(Crumbs::CRUMB_IGNORE)) return;
            if ($event->getRequest()->query->get(Crumbs::CRUMB_RESET)) {
                $crumbs->reset();
            }

            $url = Uri::create()->getRelativePath();
            $title = $page->getTitle();
            $ignore = ['', '/'];
            if (in_array($url, $ignore)) {
                $url = $crumbs->getHomeUrl();
            }
            $crumbs->trimByUrl($url);
            $crumbs->trim();
            $crumbs->addCrumb($url, $title);
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'onRequest',
            KernelEvents::VIEW => 'onView',
        ];
    }

}