<?php
namespace Bs\Listener;

use Bs\Factory;
use Bs\Mvc\Page;
use Bs\Mvc\PageInterface;
use Bs\Ui\Crumbs;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Tk\Uri;

class CrumbsHandler implements EventSubscriberInterface
{

    public function onRequest(RequestEvent $event): void
    {
        // Init the crumb stack
        Factory::instance()->getCrumbs();
    }

    public function onView(ViewEvent $event): void
    {
        $page = Factory::instance()->getPage();
        if (!($page instanceof Page)) return;

        if ($page->isEnabled() && $page->isCrumbsEnabled()) {
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