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
class LogoutDialog extends \Tk\Ui\Dialog
{


    /**
     */
    public function __construct()
    {
        parent::__construct('logoutModal', 'Ready To Leave');
        $config = \Bs\Config::getInstance();
        $this->getButtonList()->append(\Tk\Ui\Link::createBtn('Logout', 'fa fa-sign-out')
            ->addCss('btn-primary')
            ->setUrl(\Tk\Uri::create($config->get('url.auth.logout'))));
    }

    /**
     * @return \Dom\Template
     */
    public function doShow()
    {
        $template = $this->getTemplate();

        return $template;
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