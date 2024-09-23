<?php
namespace Bs\Traits;

use Au\Auth;
use Bs\Db\User;
use Bs\Ui\Crumbs;
use Dom\Template;
use Tk\Config;
use Tk\Cookie;
use Bs\Factory;
use Bs\Registry;
use Tk\System;
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

    public function makePath(string $path): string
    {
        return System::makePath($path);
    }

    public function makeUrl(string $path): string
    {
        return System::makeUrl($path);
    }

    /**
     * @deprecated use Template::load($xhtml)
     */
    public function loadTemplate(string $html = ''): ?Template
    {
        return Template::load($html);
        //return $this->getFactory()->getTemplateLoader()->load($html);
    }

    /**
     * @deprecated use Template::loadFile($xhtml)
     */
    public function loadTemplateFile(string $path = ''): ?Template
    {
        return Template::loadFile($path);
        //return $this->getFactory()->getTemplateLoader()->loadFile($path);
    }

}