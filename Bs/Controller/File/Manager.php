<?php
namespace Bs\Controller\File;

use Bs\Db\UserInterface;
use Bs\PageController;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Alert;
use Tk\Form;
use Tk\Form\FormTrait;
use Tk\FormRenderer;
use Tk\Uri;


/**
 * This manager controller is an example of how to setup the
 * \Bs\Db\File Table and \Bs\Form\Field\File field for your sites.
 *
 */
class Manager extends PageController
{
    use FormTrait;

    protected \Bs\Table\File $table;

    protected string $fkey = '';


    public function __construct()
    {
        parent::__construct($this->getFactory()->getPublicPage());
        $this->getPage()->setTitle('File Manager');
        $this->setAccess(UserInterface::PERM_ADMIN);
    }

    public function doDefault(Request $request)
    {
        // Get the form template
        $this->table = new \Bs\Table\File($this->fkey);
        $this->table->doDefault($request);

        $this->setForm(Form::create('upload'));
        $this->getForm()->appendField(new \Bs\Form\Field\File('file', $this->getFactory()->getAuthUser()))->setLabel('Create File');
        $this->getForm()->appendField(new Form\Action\Submit('save', [$this, 'onSubmit']));

        $this->getForm()->execute($request->request->all());
        $this->setFormRenderer(new FormRenderer($this->getForm()));

        return $this->getPage();
    }

    public function onSubmit(Form $form, Form\Action\ActionInterface $action)
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

        $renderer = $this->getFormRenderer();
        $this->getForm()->addCss('mb-5');
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