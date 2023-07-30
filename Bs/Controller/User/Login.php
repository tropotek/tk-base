<?php
namespace Bs\Controller\User;

use Bs\Db\User;
use Bs\Form\EditTrait;
use Bs\PageController;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Alert;
use Tk\Uri;

class Login extends PageController
{
    use EditTrait;

    public function __construct()
    {
        parent::__construct();
        $this->getPage()->setTitle('Login');

    }

    public function doLogin(Request $request): \App\Page|\Dom\Mvc\Page
    {
        $this->setForm(new \Bs\Form\Login());
        $this->getForm()->init();
        $this->getForm()->execute($request->request->all());

        return $this->getPage();
    }

    public function doLogout(Request $request): void
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


