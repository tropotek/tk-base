<?php
namespace Bs\Controller\Util;

use Bs\Auth;
use Bs\Mvc\ControllerAdmin;
use Bs\Mvc\Form;
use Dom\Template;
use Tk\Alert;
use Tk\Form\Action\Submit;
use Tk\Form\Field\File;

class InlineImage extends ControllerAdmin
{
    protected Form $form;
    protected string $base64 = '';

    public function doDefault(): void
    {
        $this->getPage()->setTitle('Image 2 Base64 inline');
        $this->setAccess(Auth::PERM_ADMIN);

        $this->form = new Form();
        $this->form->appendField((new File('file')))->setLabel('Convert File');
        $this->form->appendField(new Submit('convert', [$this, 'onSubmit']));
        $this->form->execute($_POST);

    }

    public function onSubmit(Form $form, Submit $action): void
    {
        /** @var File $fileField */
        $fileField = $form->getField('file');

        if (!$fileField->getUploaded()) {
            $form->addFieldError('file', "No file uploaded");
        }
        $fileField->isValid();

        if ($form->hasErrors()) {
            Alert::addError('Form contains errors.');
            return;
        }

        $file = $fileField->getUploaded();
        $type = pathinfo($file['full_path'], PATHINFO_EXTENSION);
        $data = strval(file_get_contents($file['tmp_name']));
        $this->base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);

    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setAttr('back', 'href', $this->getBackUrl());

        $template->appendTemplate('content', $this->form->show());

        if ($this->base64) {
            $template->setVisible('has-img');
            $template->setAttr('img', 'src', $this->base64);
            $template->setText('img-code', sprintf('<img src="%s">', $this->base64));
        }

        $css = <<<CSS
.file-convert pre {
  background-color: #EFEFEF;
  padding: 10px;
  margin: 10px;
  white-space: pre-wrap;
  text-align: left;
}
CSS;
        $template->appendCss($css);

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div class="file-convert">
  <div class="page-actions card mb-3">
    <div class="card-header"><i class="fa fa-cogs"></i> Actions</div>
    <div class="card-body" var="actions">
      <a href="/" title="Back" class="btn btn-outline-secondary" var="back"><i class="fa fa-arrow-left"></i> Back</a>
    </div>
  </div>
  <div class="card mb-3">
    <div class="card-header" var="title"><i class="fa fa-image"></i> Image 2 Base64 inline</div>
    <div class="card-body php-info">
        <div var="content"></div>
        <div class="text-center" choice="has-img">
            <hr>
            <img src="" var="img" style="border: 1px solid #CCC;">
            <p>&nbsp;</p>
            <p><pre var="img-code"></pre></p>
        </div>
    </div>
  </div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}


