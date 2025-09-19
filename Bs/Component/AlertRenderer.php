<?php
namespace Bs\Component;

use Bs\Mvc\ComponentInterface;
use Bs\Registry;
use Dom\Template;
use Tk\Alert;
use Tk\Config;
use Tk\Date;
use Tk\System;
use Tk\Uri;

/**
 *
 * @todo A work in progress, not currently working
 *       When we try to delete a session alert var it is not getting unset
 *       I am not sure if it is because of HTMX components all loading and creating new session instances or not?
 */
class AlertRenderer extends \Dom\Renderer\Renderer implements ComponentInterface
{
    const string CONTAINER_ID = 'tk-alert-renderer';
    protected array $hxTriggers = [];


    public function doDefault(): ?Template
    {

//        // Always set the htmx target and swap to end of the surrounding page <body>.
//        header('HX-Retarget: body');
//        header('HX-Reswap: beforeend');
//
//        // Send HX event headers
//        if (count($this->hxTriggers)) {
//            header(sprintf('HX-Trigger: %s', json_encode($this->hxTriggers)));
//        }

        return $this->show();
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setAttr('alertPanel', 'id', self::CONTAINER_ID);

        foreach (Alert::getAlerts() as $type => $flash) {
            foreach ($flash as $a) {
                $r = $template->getRepeat('alert');
                $css = strtolower($type);
                if ($css == 'error') $css = 'danger';
                $r->addCss('alert', 'alert-' . $css);
                //$r->setText('title', ucfirst(strtolower($type)));
                $r->setHtml('message', $a->message);
                if ($a->icon) {
                    $r->addCss('icon', $a->icon);
                    $r->setVisible('icon');
                }
                $r->appendRepeat();
            }
        }

        if ($this->getTemplate()->varExists('alert')) {
            $this->getTemplate()->insertTemplate('alert', $template);
        } else {
            $this->getTemplate()->prependTemplate('content', $template);
        }

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div var="alertPanel">
  <div class="alert alert-dismissible fade show" role="alert" repeat="alert">
    <i choice="icon"></i>
    <strong var="title"></strong>
    <span var="message"></span>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
</div>
HTML;
        return Template::load($html);
    }

}
