<?php
namespace Bs\Controller\User;

use Bs\ControllerDomInterface;
use Bs\Form;
use Dom\Template;

class Recover extends ControllerDomInterface
{
    protected ?Form $form = null;

    public function doDefault(): void
    {
        $this->getPage()->setTitle('Recover');

        $this->form = new \Bs\Form\Recover();
        $this->form->execute($_POST);
    }

    public function doRecover(): void
    {
        $this->getPage()->setTitle('Recover');

        $this->form = new \Bs\Form\RecoverPassword();
        $this->form->execute($_POST);
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