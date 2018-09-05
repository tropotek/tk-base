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
class AboutDialog extends \Tk\Ui\Dialog
{

    /**
     * Element constructor.
     */
    public function __construct()
    {
        $config = \Bs\Config::getInstance();
        parent::__construct('aboutModal', 'About' . $config->get('site.title'));
    }

    /**
     * @return \Dom\Template
     */
    public function doShow()
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