<?php
namespace Bs\Controller\User;

use Bs\Form\EditTrait;
use Bs\PageController;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Alert;
use Tk\Uri;

class Profile extends PageController
{
    use EditTrait;

    public function __construct()
    {
        parent::__construct($this->getFactory()->getPublicPage());
        $this->getPage()->setTitle('My Profile');
    }

    public function doDefault(Request $request): \App\Page|\Dom\Mvc\Page
    {
        if (!$this->getAuthUser()) {
            Alert::addError('You do not have access to this page.');
            Uri::create('/')->redirect();
        }

        // Get the form template
        $this->setForm(new \Bs\Form\Profile($this->getFactory()->getAuthUser()));
        $this->getForm()->init()->execute($request->request->all());

        return $this->getPage();
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->appendText('title', $this->getPage()->getTitle());

        //$template->appendTemplate('content', $this->form->getRenderer()->getTemplate());
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