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

        $template->setAttr('logout-url', 'href', \Tk\Uri::create($config->get('url.auth.logout')));

        return $template;
    }

    /**
     * @return \Dom\Template
     */
    public function __makeTemplate()
    {
        $html = <<<HTML
<div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="logoutModalLabel">Ready to Leave?</h5>
          <button class="close" type="button" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        </div>
        <div class="modal-body">
          <p>Select "Logout" below if you are ready to end your current session.</p>
          <p>&nbsp;</p>
        </div>
        <div class="modal-footer">
          <!--<button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>-->
          <a class="btn btn-primary" href="/logout.html" var="logout-url"><i class="fa fa-sign-out"></i> Logout</a>
        </div>
      </div>
    </div>
  </div>
HTML;
        return \Dom\Loader::load($html);
    }

}