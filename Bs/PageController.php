<?php
namespace Bs;

use Bs\Db\UserInterface;
use Bs\Ui\Crumbs;
use Tk\Alert;
use Tk\Exception;
use Tk\Log;
use Tk\Uri;

abstract class PageController extends \Dom\Mvc\PageController
{

    public function __construct(?Page $page = null)
    {
        if (!$page) {
            $pageType = $this->getFactory()->getRequest()->get('template', Page::TEMPLATE_PUBLIC);
            $page = $this->getFactory()->createPageFromType($pageType);
        }
        if (!$page) {
            //$page = $this->getFactory()->createPage();    // use this if we want to render pages with default template?
            throw new Exception('Is your route missing the `defaults([\'template\' => \'admin\'])` call?');
        }
        parent::__construct($page);
    }

    protected function setAccess(int $access): static
    {
        $user = $this->getAuthUser();
        if (!$user?->hasPermission($access)) {
            Log::error('Invalid access to controller: ' . static::class);
            Alert::addWarning('You do not have permission to access the page: <b>' . Uri::create()->getRelativePath() . '</b>');
            // TODO: get the user homepage from somewhere ???
            Uri::create('/')->redirect();
        }
        return $this;
    }

    public function getAuthUser(): ?UserInterface
    {
        return $this->getFactory()->getAuthUser();
    }

    public function getCrumbs(): ?Crumbs
    {
        return $this->getPage()?->getCrumbs();
    }

    public function getBackUrl(): Uri
    {
        return $this->getFactory()->getBackUrl();
    }

}