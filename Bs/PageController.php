<?php
namespace Bs;

use Bs\Db\UserInterface;
use Bs\Ui\Crumbs;
use Tk\Alert;
use Tk\Log;
use Tk\Uri;

abstract class PageController extends \Dom\Mvc\PageController
{

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

    public function getCrumbs(): Crumbs
    {
        return $this->getFactory()->getCrumbs();
    }

    public function getBackUrl(): Uri
    {
        return $this->getFactory()->getBackUrl();
    }

}