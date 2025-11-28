<?php
namespace Bs\Listener;

use Bs\Mvc\ControllerInterface;
use Bs\Factory;
use Bs\Mvc\PageInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Tk\Config;
use Tk\Path;

/**
 * Handles page rendering and integrates with Symfony events to dynamically manage
 * controller and page instances. The class listens to specific kernel events,
 * processes the controller and view events, and generates HTML responses accordingly.
 *
 * Implements EventSubscriberInterface to define event handling methods.
 */
class PageHandler implements EventSubscriberInterface
{

    protected ?ControllerInterface $controller = null;
    protected ?PageInterface $page = null;

    /**
     * If a controller has a TK page template,
     * store the controller and load the page template ready for the view() event.
     * Only for controllers implementing `\Bs\Mvc\ControllerInterface`
     *
     * @Event("Symfony\Component\HttpKernel\Event\ControllerEvent")
     */
    public function onController(ControllerEvent $event): void
    {
        if (!is_array($event->getController())) return;
        if (!($event->getController()[0] instanceof ControllerInterface)) return;
        $this->controller = $event->getController()[0];

        $pageTemplate = Path::create($this->controller->getPageTemplate());
        if (!is_file($pageTemplate)) {
            $pageTemplate = Path::create(Config::getValue('path.template.public', ''));
        }

        $this->page = Factory::instance()->initPage($pageTemplate);
    }

    /**
     * Insert the controller content into the page
     * template and set that as the response.
     * Only for controllers implementing `\Bs\Mvc\ControllerInterface`
     *
     * @Event("Symfony\Component\HttpKernel\Event\ViewEvent")
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