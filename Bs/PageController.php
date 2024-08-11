<?php
namespace Bs;

use Bs\Ui\Crumbs;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Tk\Alert;
use Tk\Exception;
use Tk\Log;
use Tk\Uri;

/**
 *
 * @deprecated Use \Bs\ControllerInterface
 */
abstract class PageController extends ControllerInterface
{

//    public function __construct(?PageInterface $page = null)
//    {
////        if (!$page) {
////            $pageType = $this->getFactory()->getRequest()->get('template', PageInterface::TEMPLATE_PUBLIC);
////            $page = $this->getFactory()->createPageFromType($pageType);
////        }
////        if (!$page) {
////            //$page = $this->getFactory()->createPage();    // use this if we want to render pages with default template?
////            throw new Exception('Is your route missing the `defaults([\'template\' => \'admin\'])` call?');
////        }
////        parent::__construct($page);
//    }
//
//    protected function setAccess(int $access): static
//    {
//        $user = $this->getAuthUser();
//        if (!$user?->hasPermission($access)) {
//            Log::error('Invalid access to controller: ' . static::class);
//            Alert::addWarning('You do not have permission to access the page: <b>' . Uri::create()->getRelativePath() . '</b>');
//            // TODO: get the user homepage from somewhere ???
//            Uri::create('/')->redirect();
//        }
//        return $this;
//    }
//
//    public function getAuthUser(): ?UserInterface
//    {
//        return $this->getFactory()->getAuthUser();
//    }
//
//    public function getCrumbs(): ?Crumbs
//    {
//        return $this->getPage()?->getCrumbs();
//    }
//
//    public function getBackUrl(): Uri
//    {
//        return $this->getFactory()->getBackUrl();
//    }
//
//    /**
//     * Forwards the request to another controller.
//     * NOTE: If you are using Dom\Template to generate the response, keep in mind you will lose any template headers, scripts and style tags
//     *       because this will return the response as a string and not the actual template object.
//     *
//     * @param callable|string|array $controller The controller name (a string like Bundle\BlogBundle\Controller\PostController::indexAction)
//     */
//    protected function forward(callable|string|array $controller, array $path = null, array $query = null, array $request = null): Response
//    {
//        $requestObj = $this->getFactory()->getRequest();
//        $path['_controller'] = $controller;
//        $subRequest = $requestObj->duplicate($query, $request, $path);
//        $kernel = $this->getFactory()->getFrontController();
//        return $kernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
//    }
}