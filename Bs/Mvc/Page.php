<?php
namespace Bs\Mvc;

use Bs\Auth;
use Bs\Ui\Crumbs;
use Dom\Modifier\JsLast;
use Dom\Template;
use Tk\Config;
use Tk\Uri;
use Bs\Registry;

class Page extends PageDomInterface
{
    protected bool $crumbsEnabled = true;

    public function show(): ?Template
    {
        $template = $this->getTemplate();

        $jsConfig = [
            'baseUrl' => Config::getBaseUrl(),
            'isProd'  => Config::isProd(),
            'isAuth'  => !is_null(Auth::getAuthUser()),
            'dateFormat' => [
                'jqDatepicker' => 'dd/mm/yy',
                'bsDatepicker' => 'dd/mm/yyyy',
                'sugarjs' => '%d/%m/%Y',
            ],
        ];
        $js = sprintf('let tkConfig = %s;', json_encode($jsConfig, JSON_PRESERVE_ZERO_FRACTION | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $template->appendJs($js, [JsLast::$ATTR_PRIORITY => -9999]);

        $template->setTitleText($this->getTitle());
        if (Config::isDebug()) {
            $template->setTitleText('DEBUG: ' . $template->getTitleText());
        }

        $template->setText('site-name', Registry::instance()->getSiteName());
        $template->setText('site-short-name', Registry::instance()->getSiteShortName());
        $template->setText('site-name-letter', Registry::instance()->getSitename()[0] ?? '');
        $template->setText('page-title', $this->getTitle());

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