<?php
namespace Bs;

use Bs\Db\UserInterface;
use Tk\Alert;
use Tk\Log;
use Tk\Uri;

abstract class PageController extends \Dom\Mvc\PageController
{

    protected function setAccess(int $access): static
    {
        $user = $this->getFactory()->getAuthUser();
        if (!$user?->hasPermission($access)) {
            Log::error('Invalid access to controller: ' . static::class);
            Alert::addWarning('You do not have permission to access the page: <b>' . Uri::create()->getRelativePath() . '</b>');
            // TODO: get the user homepage from somewhere ???
            Uri::create('/')->redirect();
        }
        return $this;
    }


}