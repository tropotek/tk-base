<?php
namespace Bs\Controller\File;

use Bs\ControllerDomInterface;
use Bs\Db\User;
use Bs\Table\ManagerTrait;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Alert;
use Tk\Form;
use Tk\Uri;

class Manager extends ControllerDomInterface
{
    use ManagerTrait;

    protected string $fkey = '';

    protected ?Form $form = null;


    public function doDefault(Request $request): void
    {
        $this->getPage()->setTitle('File Manager');
        $this->setAccess(User::PERM_ADMIN);


        // Get the form template
        $this->setTable(new \Bs\Table\File());
        $this->getTable()->setFkey($this->fkey);
        $this->getTable()->init();

        $filter = [];
        if ($this->fkey) {
            $filter['fkey'] = $this->fkey;
        }
        $this->getTable()->findList($filter, $this->getTable()->getTool('path'));

        $this->getTable()->execute($request);

        $this->form = Form::create('upload');
        $this->form->appendField(new \Bs\Form\Field\File('file', $this->getFactory()->getAuthUser()))->setLabel('Create File');
        $this->form->appendField(new Form\Action\Submit('save', [$this, 'onSubmit']));
        $this->form->execute($request->request->all());

    }

    public function onSubmit(Form $form, Form\Action\ActionInterface $action): void
    {
        // TODO: Validate files uploads

        if ($form->hasErrors()) {
            Alert::addError('Form contains errors.');
            return;
        }

        Alert::addSuccess('File uploaded save successfully.');
        $action->setRedirect(Uri::create());
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->appendText('title', $this->getPage()->getTitle());
        $template->setAttr('back', 'href', $this->getBackUrl());

        $renderer = new Form\Renderer\Dom\Renderer($this->form);
        $this->form->addCss('mb-5');
        $template->appendTemplate('upload', $renderer->show());

        $template->appendTemplate('content', $this->getTable()->show());

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
    <div class="card-header" var="title"><i class="fa fa-cogs"></i> </div>
    <div class="card-body">
      <div var="upload"></div>
      <div var="content"></div>
    </div>
  </div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}