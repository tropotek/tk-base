<?php
namespace Bs\Component;

use Bs\Mvc\ComponentInterface;
use Bs\Registry;
use Dom\Template;
use Tk\Config;
use Tk\Date;
use Tk\System;
use Tk\Uri;

/**
 *
 */
class AboutDialog extends \Dom\Renderer\Renderer implements ComponentInterface
{
    const string CONTAINER_ID = 'tk-about-dialog';
    protected array $hxTriggers = [];


    public function doDefault(): ?Template
    {

        // Always set the htmx target and swap to end of the surrounding page <body>.
        header('HX-Retarget: body');
        header('HX-Reswap: beforeend');

        // Send HX event headers
        if (count($this->hxTriggers)) {
            header(sprintf('HX-Trigger: %s', json_encode($this->hxTriggers)));
        }

        return $this->show();
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setAttr('dialog', 'id', self::CONTAINER_ID);

        $template->setText('site-name', Registry::getSiteName());
        $template->setText('year', date('Y'));
        $template->setText('version', System::getVersion());
        $template->setText('licence', 'Registered');
        $template->setText('released', System::getReleaseDate()->format(Date::FORMAT_LONG_DATETIME));

        $template->setHtml('copyright', 'Copyright &copy; ' . \date('Y') . ' ' . Config::getValue('developer.name', 'Undefined'));
        $template->setAttr('copyright', 'href', Uri::create(Config::getValue('developer.web', 'Undefined')));

        $template->setHtml('author', Config::getValue('developer.name', 'Undefined'));
        $template->setAttr('author', 'href', Uri::create(Config::getValue('developer.web', 'Undefined')));

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $dialogId = self::CONTAINER_ID;

        $html = <<<HTML
<div id="about-modal" class="modal fade" tabindex="-1" aria-hidden="true" var="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fs-5"><span var="site-name"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <dl class="row">
          <dt class="col-sm-3">Version</dt>
          <dd class="col-sm-9" var="version"></dd>

          <dt class="col-sm-3">Released</dt>
          <dd class="col-sm-9" var="released"></dd>

          <dt class="col-sm-3">Licence</dt>
          <dd class="col-sm-9" var="licence">Registered</dd>

          <dt class="col-sm-3">Author</dt>
          <dd class="col-sm-9"><a href="https://www.tropotek.com.au/" target="_blank" var="author">tropotek.com.au</a></dd>
        </dl>

        <p class="float-end mb-0"><small><a href="https://www.tropotek.com/" target="_blank" var="copyright">Tropotek</a></small></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>

<script>
jQuery(function($) {
    const dialog = '#{$dialogId}';

    // open the dialog as soon as HTMX settles
    $(dialog).modal('show');
    
    $(document).on('tkForm:dialogclose', function(e) {
        $(dialog).modal('hide');
    });

    // remove the dialog element from the dom when it closes
    $(dialog).on('hidden.bs.modal', function() {
        $(dialog).remove();
    });
});
</script>
</div>
HTML;
        return Template::load($html);
    }

}
