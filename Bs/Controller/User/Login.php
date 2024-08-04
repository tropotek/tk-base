<?php
namespace Bs\Controller\User;

use Bs\ControllerDomInterface;
use Bs\Db\User;
use Bs\Form\EditTrait;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Alert;
use Tk\Uri;

class Login extends ControllerDomInterface
{
    use EditTrait;

    public function doLogin(Request $request): void
    {
        $this->getPage()->setTitle('Login');

        $this->setForm(new \Bs\Form\Login());
        $this->getForm()->init();
        $this->getForm()->execute($request->request->all());

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