<?php
namespace Bs\Mvc;

use Bs\Auth;
use Bs\Traits\SystemTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Tk\Alert;
use Tk\Exception;
use Tk\Uri;
use Bs\Factory;

abstract class ControllerInterface
{
    use SystemTrait;

    protected string $pageTemplate = '';


    protected function setAccess(int $access): static
    {
        $auth = Auth::getAuthUser();
        if (!$auth || !$auth->hasPermission($access)) {
            Alert::addWarning('You do not have permission to access the page: <b>' . Uri::create()->getRelativePath() . '</b>');
            $auth?->getHomeUrl()->redirect();
            Uri::create('/')->redirect();
        }
        return $this;
    }

    public function getPageTemplate(): string
    {
        return $this->pageTemplate;
    }

    protected function setPageTemplate(string $pageTemplate): ControllerInterface
    {
        $this->pageTemplate = $pageTemplate;
        return $this;
    }

    public function getPage(): PageDomInterface|PageInterface
    {
        $page = Factory::instance()->getPage();
        if (is_null($page)) {
            throw new Exception("Controller Page not found");
        }
        return $page;
    }

    /**
     * Forwards the request to another controller.
     * NOTE: If you are using Dom\Template to generate the response, keep in mind you will lose any template headers, scripts and style tags
     *       because this will return the response as a string and not the actual template object.
     *
     * @param callable|string|array $controller The controller name (a string like Bundle\BlogBundle\Controller\PostController::indexAction)
     */
    protected function forward(callable|string|array $controller, array $path = null, array $query = null, array $request = null): Response
    {
        $requestObj = Factory::instance()->getRequest();
        $path['_controller'] = $controller;
        $subRequest = $requestObj->duplicate($query, $request, $path);
        $kernel = Factory::instance()->getFrontController();
        return $kernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
    }
}