<?php
namespace Bs\Controller\User;

use Bs\Form\EditTrait;
use Dom\Mvc\PageController;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;

class Recover extends PageController
{
    use EditTrait;


    public function __construct()
    {
        parent::__construct($this->getFactory()->getLoginPage());
        $this->getPage()->setTitle('Recover');
    }

    public function doDefault(Request $request): \App\Page|\Dom\Mvc\Page
    {
        $this->setForm(new \Bs\Form\Recover());
        $this->getForm()->init();
        $this->getForm()->execute($request->request->all());

        return $this->getPage();
    }

    public function doRecover(Request $request): \App\Page|\Dom\Mvc\Page
    {
        $this->setForm(new \Bs\Form\RecoverPassword());
        $this->getForm()->init()->execute($request->request->all());

        return $this->getPage();
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
    <h1 class="h3 mb-3 fw-normal text-center">Recover Account</h1>
    <div class="" var="content"></div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}


