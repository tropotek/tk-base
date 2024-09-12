<?php
namespace Bs;

use Bs\Ui\Crumbs;
use Dom\Modifier\JsLast;
use Dom\Template;
use Tk\System;
use Tk\Uri;

class Page extends PageDomInterface
{
    protected bool $crumbsEnabled = true;

    public function show(): ?Template
    {
        $template = $this->getTemplate();

        $jsConfig = [
            'baseUrl' => $this->getConfig()->getBaseUrl(),
            'dataUrl' => $this->getConfig()->getDataUrl(),
            'templateUrl' => $this->getConfig()->getTemplateUrl(),
            'vendorUrl' => System::makeUrl($this->getConfig()->get('path.vendor')),
            'vendorOrgUrl' => System::makeUrl($this->getConfig()->get('path.vendor.org')),
            'debug' => $this->getConfig()->isDebug(),
            'isProd' => $this->getConfig()->isProd(),
            'isDev' => $this->getConfig()->isDev(),
            'dateFormat' => [
                'jqDatepicker' => 'dd/mm/yy',
                'bsDatepicker' => 'dd/mm/yyyy',
                'sugarjs' => '%d/%m/%Y',
            ],
        ];
        $js = sprintf('let tkConfig = %s;', json_encode($jsConfig, JSON_PRESERVE_ZERO_FRACTION | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $template->appendJs($js, [JsLast::$ATTR_PRIORITY => -9999]);

        $template->setTitleText($this->getTitle());
        if ($this->getConfig()->isDebug()) {
            $template->setTitleText('DEBUG: ' . $template->getTitleText());
        }

        $template->setText('site-name', Registry::instance()->getSiteName());
        $template->setText('site-short-name', Registry::instance()->getSiteShortName());
        $template->setText('site-name-letter', Registry::instance()->getSitename()[0] ?? '');
        $template->setText('page-title', $this->getTitle());

        $user = $this->getFactory()->getAuthUser();
        if ($user) {
            $template->setText('username', $user->username);
            $template->setText('user-name', $user->getName());
            $template->setText('user-type', ucfirst($user->type));
            $template->setAttr('user-image', 'src', $user->getImageUrl());
            $template->setAttr('user-home-url', 'href', $user->getHomeUrl());

            $template->setVisible('loggedIn');
        } else {
            $template->setVisible('loggedOut');
        }

        // Default crumbs css (probably not the best place for this...
        $this->getCrumbs()->addCss('p-2 bg-body-tertiary rounded-2');

        return parent::show();
    }

    public function getCrumbs(): ?Crumbs
    {
        return $this->getFactory()->getCrumbs();
    }

    public function getBackUrl(): Uri
    {
        return $this->getFactory()->getBackUrl();
    }

    public function isCrumbsEnabled(): bool
    {
        return $this->crumbsEnabled;
    }

    public function setCrumbsEnabled(bool $crumbsEnabled): static
    {
        $this->crumbsEnabled = $crumbsEnabled;
        return $this;
    }

}