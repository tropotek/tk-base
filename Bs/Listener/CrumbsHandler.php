<?php
namespace Bs\Listener;

use Bs\Ui\Crumbs;
use Dom\Mvc\Page;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Tk\Traits\SystemTrait;
use Tk\Uri;

class CrumbsHandler implements EventSubscriberInterface
{
    use SystemTrait;

    public function onView(ViewEvent $event)
    {
        $page = $event->getControllerResult();
        if ($page instanceof Page) {
            $crumbs = $this->getFactory()->getCrumbs();
            if (!$crumbs || $event->getRequest()->query->get(Crumbs::CRUMB_IGNORE)) return;
            if ($event->getRequest()->query->get(Crumbs::CRUMB_RESET)) {
                $crumbs->reset();
            }

            $ignore = ['', '/'];
            if (in_array(Uri::create()->getRelativePath(), $ignore)) return;

            $title = $page->getTitle();
            $crumbs->trimByTitle($title);

            $crumbs->trimByUrl(Uri::create());

            $crumbs->addCrumb($title, \Tk\Uri::create());

        }
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => 'onView',
        ];
    }

}