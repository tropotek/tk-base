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
class AboutDialog extends \Tk\Ui\Dialog\Dialog
{

    /**
     * Element constructor.
     */
    public function __construct()
    {
        $config = \Bs\Config::getInstance();
        parent::__construct('About ' . $config->get('site.title'), 'aboutModal');
    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        /** @var \Dom\Template $dialogTemplate */
        $dialogTemplate = $this->__makeDialogTemplate();
        $config = \Bs\Config::getInstance();
        $dialogTemplate->insertText('title', $config->get('site.title'));
        $dialogTemplate->insertText('version', $config->get('system.info.version'));
        $dialogTemplate->insertText('authors', $config->get('system.info.authors'));
        $dialogTemplate->insertText('released', $config->get('system.info.released'));
        $dialogTemplate->insertText('licence', $config->get('system.info.licence'));
        $dialogTemplate->insertText('description', $config->get('system.info.description'));
        $this->setContent($dialogTemplate);

        $template = parent::show();

        return $template;
    }

    /**
     * @return \Dom\Template
     */
    public function __makeDialogTemplate()
    {
        $html = <<<HTML
<div>
  <p>
    Version: <span var="version"></span><br/>
    Released: <span var="released"></span><br/>
    Licence: <span var="licence"></span><br/>
    Authors:  <span var="authors"></span><br/>
    Description: <span var="description"></span><br/>
  </p>
  <p class=""><small>Copyright © <a href="https://www.tropotek.com/">tropotek.com</a> 2018</small></p>
</div>
HTML;
        return \Dom\Loader::load($html);
    }

}