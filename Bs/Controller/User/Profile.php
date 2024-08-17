<?php
namespace Bs\Controller\User;

use Bs\ControllerDomInterface;
use Dom\Template;
use Tk\Alert;
use Tk\Uri;

class Profile extends ControllerDomInterface
{

    protected ?\Bs\Form\Profile $form = null;


    public function doDefault(): void
    {
        $this->getPage()->setTitle('My Profile');

        if (!$this->getAuthUser()) {
            Alert::addError('You do not have access to this page.');
            Uri::create('/')->redirect();
        }

        // Get the form template
        $this->form = new \Bs\Form\Profile($this->getFactory()->getAuthUser());
        $this->form->execute($_POST);

    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->appendText('title', $this->getPage()->getTitle());
        $template->setAttr('back', 'href', $this->getBackUrl());

        $template->appendTemplate('content', $this->form->show());

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
  <div class="card mb-3">
    <div class="card-header"><i class="fa fa-cogs"></i> Actions</div>
    <div class="card-body" var="actions">
      <a href="/" title="Back" class="btn btn-outline-secondary" var="back"><i class="fa fa-arrow-left"></i> Back</a>
    </div>
  </div>
  <div class="card mb-3">
    <div class="card-header" var="title"><i class="fa fa-user"></i> </div>
    <div class="card-body" var="content"></div>
  </div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}