<?php
namespace Bs\Traits;

use Bs\Ui\Crumbs;
use Tk\Config;
use Tk\Http\Cookie;
use Bs\Factory;
use Bs\Registry;
use Tk\Uri;

trait SystemTrait
{

    public function getFactory(): Factory
    {
        return Factory::instance();
    }

    public function getConfig(): Config
    {
        return Config::instance();
    }

    public function getRegistry(): Registry
    {
        return Registry::instance();
    }

    public function getCookie(): Cookie
    {
        return $this->getFactory()->getCookie();
    }

    public function getCrumbs(): ?Crumbs
    {
        return Factory::instance()->getCrumbs();
    }

    public function getBackUrl(): Uri
    {
        return Factory::instance()->getBackUrl();
    }

}