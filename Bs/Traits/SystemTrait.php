<?php
namespace Bs\Traits;

use Bs\Db\User;
use Bs\Ui\Crumbs;
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

    public function getAuthUser(): ?User
    {
        return Factory::instance()->getAuthUser();
    }

    public function makePath(string $path): string
    {
        return System::makePath($path);
    }

    public function makeUrl(string $path): string
    {
        return System::makeUrl($path);
    }

    public function loadTemplate(string $xhtml = ''): ?\Dom\Template
    {
        return $this->getFactory()->getTemplateLoader()->load($xhtml);
    }

    public function loadTemplateFile(string $path = ''): ?\Dom\Template
    {
        return $this->getFactory()->getTemplateLoader()->loadFile($path);
    }

}