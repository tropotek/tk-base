<?php
namespace Bs\Ui;


/**
 * @author Michael Mifsud <http://www.tropotek.com/>
 * @see http://www.tropotek.com/
 * @license Copyright 2017 Michael Mifsud
 */
class LogoutDialog extends \Tk\Ui\Dialog\Dialog
{

    /**
     * LogoutDialog constructor.
     */
    public function __construct()
    {
        parent::__construct('Ready To Leave', 'logoutModal');
        $config = \Bs\Config::getInstance();
        $this->getButtonList()->append(\Tk\Ui\Link::createBtn('Logout', 'fa fa-sign-out')
            ->addCss('btn-primary')
            ->setUrl(\Tk\Uri::create($config->get('url.auth.logout'))));
    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $this->setContent($this->__makeDialogTemplate());
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
  <p>Select "Logout" below if you are ready to end your current session.</p>
  <p>&nbsp;</p>
</div>
HTML;
        return \Dom\Loader::load($html);
    }

}