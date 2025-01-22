<?php
namespace Bs\Mvc;

use Bs\Auth;
use Dom\Template;
use Tk\Config;
use Tk\Uri;
use Bs\Registry;

class Page extends PageDomInterface
{

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
        $template->appendJs($js);

        $template->setTitleText($this->getTitle());
        if (Config::isDebug()) {
            $template->setTitleText('DEBUG: ' . $template->getTitleText());
        }

        $template->setText('site-name', Registry::instance()->getSiteName());
        $template->setAttr('site-short-name', 'title', Registry::instance()->getSiteName());
        $template->setText('site-short-name', Registry::instance()->getSiteShortName());
        $template->setAttr('site-name-letter', 'title', Registry::instance()->getSiteName());
        $template->setText('site-name-letter', Registry::instance()->getSitename()[0] ?? '');
        $template->setText('page-title', $this->getTitle());

        return parent::show();
    }

    public function getBackUrl(): Uri
    {
        return $this->getFactory()->getBackUrl();
    }

}