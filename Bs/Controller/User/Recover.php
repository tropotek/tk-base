<?php
namespace Bs\Controller\User;

use Bs\ControllerDomInterface;
use Bs\Form;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;

class Recover extends ControllerDomInterface
{
    protected ?Form $form = null;

    public function doDefault(Request $request): void
    {
        $this->getPage()->setTitle('Recover');

        $this->form = new \Bs\Form\Recover();
        $this->form->execute($request->request->all());
    }

    public function doRecover(Request $request): void
    {
        $this->getPage()->setTitle('Recover');

        $this->form = new \Bs\Form\RecoverPassword();
        $this->form->execute($request->request->all());
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