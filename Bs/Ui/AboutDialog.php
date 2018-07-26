<?php
namespace Bs\Ui;


/**
 * Add the following to a URL to open the dialog:
 *
 * <a href="#" data-toggle="modal" data-target="#aboutModal"><i class="fa fa-info-circle"></i>About</a>
 *
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2017 Michael Mifsud
 */
class AboutDialog extends \Dom\Renderer\Renderer implements \Dom\Renderer\DisplayInterface
{


    /**
     * Element constructor.
     */
    public function __construct()
    {
    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        /** @var \Dom\Template $template */
        $template = $this->getTemplate();
        $config = \Bs\Config::getInstance();

        $template->insertText('title', $config->get('site.title'));
        $template->insertText('version', $config->get('system.info.version'));
        $template->insertText('authors', $config->get('system.info.authors'));
        $template->insertText('released', $config->get('system.info.released'));
        $template->insertText('licence', $config->get('system.info.licence'));
        $template->insertText('description', $config->get('system.info.description'));

        return $template;
    }

    /**
     * @return \Dom\Template
     */
    public function __makeTemplate()
    {
        $html = <<<HTML
<div class="modal fade" id="aboutModal" tabindex="-1" role="dialog" aria-labelledby="aboutModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="aboutModalLabel" var="title"></h5>
        <button class="close" type="button" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
      </div>
      <div class="modal-body">
        <p>
          Version: <span var="version"></span><br/>
          Released: <span var="released"></span><br/>
          Licence: <span var="licence"></span><br/>
          Authors:  <span var="authors"></span><br/>
          Description: <span var="description"></span><br/>
        </p>
        <p class=""><small>Copyright © <a href="https://www.tropotek.com/">tropotek.com</a> 2018</small></p>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
HTML;
        return \Dom\Loader::load($html);
    }

}