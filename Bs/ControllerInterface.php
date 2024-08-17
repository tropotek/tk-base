<?php
namespace Bs;

use Bs\Db\User;
use Bs\Ui\Crumbs;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Tk\Alert;
use Tk\Log;
use Tk\Traits\SystemTrait;
use Tk\Uri;

abstract class ControllerInterface
{
    use SystemTrait;


    protected function setAccess(int $access): static
    {
        $user = $this->getAuthUser();
        if (!$user || !$user->hasPermission($access)) {
            Log::error('Invalid access to controller: ' . static::class);
            Alert::addWarning('You do not have permission to access the page: <b>' . Uri::create()->getRelativePath() . '</b>');
            $this->getBackUrl()->redirect();
        }
        return $this;
    }

    public function getPage(): PageInterface
    {
        return $this->getFactory()->getPage();
    }

    public function getBackUrl(): Uri
    {
        return $this->getFactory()->getBackUrl();
    }

    public function getAuthUser(): ?User
    {
        return $this->getFactory()->getAuthUser();
    }

    public function getCrumbs(): ?Crumbs
    {
        return $this->getFactory()->getCrumbs();
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