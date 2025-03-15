<?php
namespace Bs\Controller\Admin\Dev;

use Bs\Auth;
use Bs\Mvc\ControllerAdmin;
use Dom\Template;

class Info extends ControllerAdmin
{

    public function doDefault(): void
    {
        $this->getPage()->setTitle('PHP Info', 'fab fa-php');
        $this->setUserAccess(Auth::PERM_ADMIN);
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->appendText('title', $this->getPage()->getTitle());
        $template->addCss('icon', $this->getPage()->getIcon());

        ob_start();
        phpinfo();
        $ob = ob_get_clean();
        $ob1 = tidy_repair_string(strval($ob), ['output-xhtml' => true, 'show-body-only' => true], 'utf8');
        $template->appendHtml('content', strval($ob1));


        $js = <<<JS
jQuery(function($) {
    $('.php-info table').addClass('table table-striped');
    $('.php-info table td:not(:first-child)').addClass('text-center');
    $('.php-info table td:first-child').addClass('fw-bold text-nowrap');
});
JS;
        $template->appendJs($js);

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div class="card mb-3">
  <div class="card-header"><i var="icon"></i> <span var="title"></span></div>
  <div class="card-body php-info" var="content"></div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}


