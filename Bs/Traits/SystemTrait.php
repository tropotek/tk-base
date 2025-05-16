<?php
namespace Bs\Traits;

use Bs\Ui\Breadcrumbs;
use Tk\Config;
use Tk\Cookie;
use Bs\Factory;
use Bs\Registry;
use Tk\Uri;

/**
 * @deprecated
 */
trait SystemTrait
{

    /**
     * @deprecated
     */
    public function getFactory(): Factory
    {
        return Factory::instance();
    }

    /**
     * @deprecated
     */
    public function getConfig(): Config
    {
        return Config::instance();
    }

    /**
     * @deprecated
     */
    public function getRegistry(): Registry
    {
        return Registry::instance();
    }

    /**
     * @deprecated
     */
    public function getCookie(): Cookie
    {
        return Factory::instance()->getCookie();
    }

    /**
     * @deprecated use Breadcrumbs::getBackUrl()
     */
    public function getBackUrl(): Uri
    {
        return Breadcrumbs::getBackUrl();
    }

}