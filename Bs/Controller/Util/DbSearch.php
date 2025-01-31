<?php
namespace Bs\Controller\Util;

use Bs\Auth;
use Bs\Mvc\ControllerAdmin;
use Bs\Mvc\Form;
use Dom\Template;
use Tk\Alert;
use Tk\Db;
use Tk\Form\Action\Submit;
use Tk\Form\Field\Checkbox;
use Tk\Form\Field\File;
use Tk\Form\Field\Input;
use Tk\Uri;

class DbSearch extends ControllerAdmin
{
    protected Form $form;
    protected string $results = '';

    public function doDefault(): void
    {
        $this->getPage()->setTitle('Database Column Search');
        $this->setUserAccess(Auth::PERM_ADMIN);

        $this->form = new Form();

        $this->form->appendField(new Input('table'))
            ->setNotes('(optional) Restrict search to one table');

        $this->form->appendField(new Input('column'))
            ->setRequired()
            ->setNotes('(required) Column name to search for');

        $this->form->appendField(new Input('value'))
            ->setNotes('(optional) Restrict search to column containing the value');

        $this->form->appendField(new Checkbox('views', ['y' => 'Yes']))
            ->setValue('y')
            ->setNotes('(optional) Restrict search to column containing the value');

        $this->form->appendField(new Submit('search', [$this, 'onSubmit']));
        $this->form->execute($_POST);

    }

    public function onSubmit(Form $form, Submit $action): void
    {
        if (empty($form->getFieldValue('column'))) {
            $form->addFieldError('column', "Please provide a valid column name");
        }

        if ($form->hasErrors()) {
            Alert::addError('Form contains errors.');
            return;
        }

        $this->results = $this->DbSearchColumn(
            $form->getFieldValue('column'),
            $form->getFieldValue('value'),
            $form->getFieldValue('table'),
            truefalse($form->getFieldValue('views'))
        );

    }

    function DbSearchColumn(string $column, string $value = '', string $table = '', bool $views = false): string
    {
        $html = '';

        $dbname = Db::getDbName();
        $tableName = empty($table) ? '' : "AND TABLE_NAME = '$table'";
        $tables = Db::query("SELECT table_name FROM information_schema.tables WHERE table_schema = '{$dbname}' {$tableName}");

        foreach($tables as $table) {
            if (!$views && str_starts_with($table->table_name, 'v_')) continue;

            $columns = Db::query("SHOW COLUMNS FROM {$table->table_name} WHERE Field = :column", compact('column'));
            if (!count($columns)) continue;

            $html .= '<ul>';
            $html .= sprintf('<b>%s</b>', $table->table_name);

            if ($value !== '') {
                foreach ($columns as $col) {
                    if ($column != $col->Field) continue;
                    $rows = Db::query("SELECT * FROM {$table->table_name} WHERE {$column} = :value", ['value' => $value]);
                    if (!count($rows)) continue;
                    $html .= '<ul>';
                    foreach ($rows as $row) {
                        $html .= sprintf('<pre>%s</pre>', json_encode($row));
                    }
                    $html .= '</ul>';
                }
            }
            $html .= '</ul>';
        }

        return $html;
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setAttr('back', 'href', $this->getBackUrl());

        $template->appendTemplate('content', $this->form->show());

        if (!empty($this->results)) {
            $template->setVisible('has-results');
            $template->setHtml('results', $this->results);
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
    <div class="card-header" var="title"><i class="fas fa-database"></i> Database Column Search</div>

    <div class="card-body php-info">

        <h3>DB Table Search</h3>
        <p>Use this form to search all tables for a column name or column name containing a specific value.</p>
        <div var="content"></div>

        <div class="" choice="has-results">
            <hr>
            <div var="results"></div>
            <p>&nbsp;</p>
        </div>

    </div>

  </div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}

