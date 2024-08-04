<?php
namespace Bs\Listener;

use Bs\ControllerInterface;
use Bs\PageInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Tk\Traits\SystemTrait;

class PageHandler implements EventSubscriberInterface
{
    use SystemTrait;

    protected ?ControllerInterface $controller = null;
    protected ?PageInterface $page = null;

    /**
     * @Event("Symfony\Component\HttpKernel\Event\RequestEvent")
     */
    public function onRequest(RequestEvent $event): void
    {
        // create page from template path
        $pageType = $event->getRequest()->attributes->get('template');
        if (!empty($pageType)) {
            $this->page = $this->getFactory()->getPage($this->getSystem()->makePath($this->getConfig()->get('path.template.' . $pageType)));
        }
    }

    /**
     * @Event("Symfony\Component\HttpKernel\Event\ControllerEvent")
     */
    public function onController(ControllerEvent $event): void
    {
        if (!is_array($event->getController())) return;
        if (!($event->getController()[0] instanceof ControllerInterface)) return;
        $this->controller = $event->getController()[0];
    }

    /**
     * kernel.view
     */
    public function onView(ViewEvent $event): void
    {
        if (is_null($this->page) || !$this->page->isEnabled()) return;
        $result = $event->getControllerResult() ?? $this->controller;
        $this->page->addContent($result, 'content');
        $event->setResponse(new Response($this->page->getHtml()));
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'onRequest',
            KernelEvents::CONTROLLER => 'onController',
            KernelEvents::VIEW => [
                ['onView', -99]
            ]
        ];
    }
}