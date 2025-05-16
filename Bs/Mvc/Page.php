<?php
namespace Bs\Mvc;

use Bs\Auth;
use Bs\Ui\Breadcrumbs;
use Dom\Template;
use Tk\Config;
use Tk\Uri;
use Bs\Registry;

class Page extends PageDomInterface
{

    public function show(): ?Template
    {
        $template = $this->getTemplate();

        $url = Uri::create();
        $jsConfig = [
            'hostUrl' => $url->getScheme() . '://' . $url->getHost(),
            'baseUrl' => Config::getBaseUrl(),
            'isProd'  => Config::isProd(),
            'isAuth'  => !is_null(Auth::getAuthUser()),
        ];
        $js = sprintf('tkConfig = Object.assign({}, tkConfig, %s);', json_encode($jsConfig, JSON_PRESERVE_ZERO_FRACTION | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $template->appendHeadJs($js);

        $template->setTitleText($this->getTitle());
        if (Config::isDev()) {
            $template->setTitleText('DEV: ' . $template->getTitleText());
        }

        $template->setText('site-name', Registry::getSiteName());
        $template->setAttr('site-short-name', 'title', Registry::getSiteName());
        $template->setText('site-short-name', Registry::getSiteShortName());
        $template->setAttr('site-name-letter', 'title', Registry::getSiteName());
        $template->setText('site-name-letter', Registry::getSitename()[0] ?? '');
        $template->setText('page-title', $this->getTitle());
        if (!empty($this->getIcon())) {
            $template->addCss('page-icon', $this->getIcon());
        }

        return parent::show();
    }

    /**
     * @deprecated use Breadcrumbs::getBackUrl()
     */
    public function getBackUrl(): Uri
    {
        return Breadcrumbs::getBackUrl();
    }

}