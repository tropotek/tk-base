<?php
namespace Bs\Controller\File;

use Bs\ControllerDomInterface;
use Bs\Db\Permissions;
use Bs\Table\File;
use Dom\Template;
use Tk\Alert;
use Tk\Form;
use Tk\Uri;
use Tt\Db;

class Manager extends ControllerDomInterface
{

    protected string $fkey = '';
    protected ?Form $form = null;
    protected ?File $table = null;


    public function doDefault(): void
    {
        $this->getPage()->setTitle('File Manager');
        $this->setAccess(Permissions::PERM_ADMIN);


        // Get the form template
        $this->table = new \Bs\Table\File();
        $this->table->setOrderBy('path');
        $this->table->setFkey($this->fkey);
        $this->table->execute();

        // Set the table rows
        $filter = $this->table->getDbFilter();
        if ($this->fkey) {
            $filter->set('fkey', $this->fkey);
        }
        $rows = \Bs\Db\File::findFiltered($filter);
        $this->table->setRows($rows, Db::getLastStatement()->getTotalRows());

        // setup the upload file form
        $this->form = Form::create('upload');
        $this->form->appendField(new \Bs\Form\Field\File('file', $this->getFactory()->getAuthUser()))->setLabel('Create File');
        $this->form->appendField(new Form\Action\Submit('save', [$this, 'onSubmit']));
        $this->form->execute($_POST);

    }

    public function onSubmit(Form $form, Form\Action\ActionInterface $action): void
    {
        // TODO: Validate files uploads
        /** @var \Bs\Form\Field\File $file */
        $file = $form->getField('file');
        if (!count($file->getUploads())) {
            $form->addFieldError('file', "No file uploaded");
        }

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

        $template->appendTemplate('content', $this->table->show());

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