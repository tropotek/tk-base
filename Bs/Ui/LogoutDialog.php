<?php
namespace Bs\Ui;


use Bs\Uri;

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
        $logoutUrl = Uri::create($config->get('url.auth.logout'));
//        if ($this->getConfig()->get('auth.microsoft.enabled', false)) {
//            $logoutUrl = Uri::create($this->getConfig()->get('auth.microsoft.logout', '/'))
//                //->set('client_id', $this->getConfig()->get('auth.microsoft.clientid'))
//                //->set('id_token_hint', $this->getConfig()->get('auth.microsoft.clientid'))
//                ->set('post_logout_redirect_uri', Uri::create('/microsoftLogout.html')->toString());
//        }

        $this->getButtonList()->append(\Tk\Ui\Link::createBtn('Logout', 'fa fa-sign-out')
            ->addCss('btn-primary')
            ->setUrl($logoutUrl));
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