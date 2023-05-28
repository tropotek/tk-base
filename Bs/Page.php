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

        // TODO: Show a maintenance ribbon on the site???
//        if (!$this->getConfig()->get('system.maintenance.enabled')) return;
//        $controller = \Tk\Event\Event::findControllerObject($event);
//        if ($controller instanceof \Bs\Controller\Iface && !$controller instanceof \Bs\Controller\Maintenance) {
//            $page = $controller->getPage();
//            if (!$page) return;
//            $template = $page->getTemplate();
//
//            $html = <<<HTML
//<div class="tk-ribbon tk-ribbon-danger" style="z-index: 99999"><span>Maintenance</span></div>
//HTML;
//            $template->prependHtml($template->getBodyElement(), $html);
//            $template->addCss($template->getBodyElement() ,'tk-ribbon-box');
//        }


        return parent::show();
    }

}