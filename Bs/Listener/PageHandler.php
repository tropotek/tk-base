<?php
namespace Bs\Listener;

use Bs\Mvc\ControllerInterface;
use Bs\Factory;
use Bs\Mvc\PageInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Tk\Config;

class PageHandler implements EventSubscriberInterface
{

    protected ?ControllerInterface $controller = null;
    protected ?PageInterface $page = null;

    /**
     * @Event("Symfony\Component\HttpKernel\Event\ControllerEvent")
     */
    public function onController(ControllerEvent $event): void
    {
        if (!is_array($event->getController())) return;
        if (!($event->getController()[0] instanceof ControllerInterface)) return;
        $this->controller = $event->getController()[0];

        $pageTemplate = Config::makePath($this->controller->getPageTemplate());
        if (!is_file($pageTemplate)) {
            $pageTemplate = Config::makePath(Config::instance()->get('path.template.public', ''));
        }
        $this->page = Factory::instance()->initPage($pageTemplate);
    }

    /**
     * kernel.view
     */
    public function onView(ViewEvent $event): void
    {
        if (!is_null($event->getControllerResult())) return;
        if (is_null($this->page) || !$this->page->isEnabled()) return;
        $result = $this->controller;
        $this->page->addContent($result, 'content');
        $event->setResponse(new Response($this->page->getHtml()));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onController',
            KernelEvents::VIEW => ['onView', -99]
        ];
    }
}