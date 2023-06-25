<?php
namespace Bs\Controller\User;

use Bs\Db\User;
use Dom\Mvc\PageController;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Alert;
use Tk\Uri;

class Login extends PageController
{
    protected ?\Bs\Form\Login $form;

    public function __construct()
    {
        parent::__construct($this->getFactory()->getLoginPage());
        $this->getPage()->setTitle('Login');

    }

    public function doLogin(Request $request)
    {
        $this->form = new \Bs\Form\Login();

        $this->form->doDefault($request);

        return $this->getPage();
    }

    public function doLogout(Request $request)
    {
        User::logout(true);
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
    <div class="" var="content"></div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}

