<?php
namespace Bs;

use Dom\Template;

class Page extends \Dom\Mvc\Page
{

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
  debug          : $isDebug,
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

}