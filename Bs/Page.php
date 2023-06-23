<?php
namespace Bs;

use Bs\Ui\Crumbs;
use Dom\Template;
use Tk\Uri;

class Page extends \Dom\Mvc\Page
{
    protected bool $crumbEnabled = true;

    public function show(): ?Template
    {
        $template = $this->getTemplate();

        $isDebug = $this->getConfig()->isDebug() ? 'true' : 'false';

        $js = <<<JS
let config = {
  baseUrl        : '{$this->getConfig()->getBaseUrl()}',
  dataUrl        : '{$this->getConfig()->getDataUrl()}',
  templateUrl    : '{$this->getConfig()->getTemplateUrl()}',
  vendorUrl      : '{$this->getSystem()->makeUrl($this->getConfig()->get('path.vendor'))}',
  vendorOrgUrl   : '{$this->getSystem()->makeUrl($this->getConfig()->get('path.vendor.org'))}',
  debug          : {$isDebug},
  dateFormat: {
    jqDatepicker : 'dd/mm/yy',
    bsDatepicker : 'dd/mm/yyyy',
    sugarjs      : '%d/%m/%Y',
  }
}
JS;
        $template->appendJs($js, array('data-jsl-priority' => -1000));

        $template->setTitleText($this->getTitle());
        if ($this->getConfig()->isDebug()) {
            $template->setTitleText('DEBUG: ' . $template->getTitleText());
        }

        if ($this->getFactory()->getAuthUser()) {
            $template->setVisible('loggedIn');
        } else {
            $template->setVisible('loggedOut');
        }


        return parent::show();
    }


    public function getCrumbs(): ?Crumbs
    {
        return $this->getFactory()->getCrumbs();
    }

    public function getBackUrl(): Uri
    {
        return Uri::create($this->getCrumbs()->getBackUrl());
    }

    public function isCrumbEnabled(): bool
    {
        return $this->crumbEnabled;
    }

    public function setCrumbEnabled(bool $crumbEnabled): static
    {
        $this->crumbEnabled = $crumbEnabled;
        return $this;
    }

}