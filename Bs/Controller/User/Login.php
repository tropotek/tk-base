<?php
namespace Bs\Controller\User;

use Bs\ControllerAdmin;
use Bs\Db\User;
use Dom\Template;
use Tk\Alert;
use Tk\Uri;

class Login extends ControllerAdmin
{
    protected ?\Bs\Form\Login $form = null;

    public function __construct()
    {
        $this->setPageTemplate($this->getConfig()->get('path.template.login'));
    }

    public function doLogin(): void
    {
        $this->getPage()->setTitle('Login');

        $this->form = new \Bs\Form\Login();
        $this->form->execute($_POST);
    }

    public function doLogout(): void
    {
        User::logout();
        Alert::addSuccess('Logged out successfully');
        Uri::create('/')->redirect();
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();

        if ($this->form) {
            $template->appendTemplate('content', $this->form->show());
        }

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
    <h1 class="text-center h3 mb-3 fw-normal">Login</h1>
    <div var="content"></div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}