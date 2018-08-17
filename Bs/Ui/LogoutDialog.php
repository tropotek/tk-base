<?php
namespace Bs\Ui;


/**
 * Add the following to a URL to open the dialog:
 *
 * <a href="#" data-toggle="modal" data-target="#logoutModal"><i class="fa fa-info-circle"></i>About</a>
 *
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2017 Michael Mifsud
 */
class LogoutDialog extends \Dom\Renderer\Renderer implements \Dom\Renderer\DisplayInterface
{
    /**
     * @var null|\Tk\Ui\Dialog
     */
    protected $dialog = null;


    /**
     */
    public function __construct()
    {
        $config = \Bs\Config::getInstance();
        $this->dialog = \Tk\Ui\Dialog::create('logoutModal', 'Ready To Leave');
        $this->dialog->getButtonList()->append(\Tk\Ui\Link::createBtn('Logout', 'fa fa-sign-out')
            ->addCss('btn-primary')
            ->setUrl(\Tk\Uri::create($config->get('url.auth.logout'))));
    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        /** @var \Dom\Template $template */
        $template = $this->getTemplate();

        $this->dialog->setContent($template);
        return $this->dialog->show();
    }

    /**
     * @return \Dom\Template
     */
    public function __makeTemplate()
    {
        $html = <<<HTML
<div>
  <p>Select "Logout" below if you are ready to end your current session.</p>
  <p>&nbsp;</p>
</div>
HTML;
        return \Dom\Loader::load($html);
    }

}