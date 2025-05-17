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
use Tk\Form\Field\Input;

class DbSearch extends ControllerAdmin
{
    protected Form $dbSearch;
    protected Form $dbValue;
    protected string $results = '';
    protected string $valResults = '';

    public function doDefault(): void
    {
        $this->getPage()->setTitle('Database Utils', 'fas fa-database');
        $this->setUserAccess(Auth::PERM_ADMIN);

        $this->dbSearch = new Form();
        $this->dbSearch->appendField(new Input('table'))
            ->setNotes('(optional) Restrict search to one table');
        $this->dbSearch->appendField(new Input('column'))
            ->setRequired()
            ->setNotes('(required) Column name to search for');
        $this->dbSearch->appendField(new Input('value'))
            ->setNotes('(optional) Restrict search to column containing the value');
        $this->dbSearch->appendField((new Checkbox('views', ['y' => 'Yes']))
            ->setValue('y')
            ->setSwitch(true)
        );
        $this->dbSearch->appendField(new Submit('search', [$this, 'onDbSearch']));
        $this->dbSearch->execute($_POST);


        $this->dbValue = new Form();
        $this->dbValue->appendField(new Input('value'))
            ->setNotes('Search tables containing this value');
        $this->dbValue->appendField(new Submit('search', [$this, 'onDbValue']));
        $this->dbValue->execute($_POST);

    }

    public function onDbValue(Form $form, Submit $action): void
    {
        if (empty($form->getFieldValue('value'))) {
            $form->addFieldError('value', "Please provide a valid value to search for");
        }

        if ($form->hasErrors()) {
            Alert::addError('Form contains errors.');
            return;
        }

        $results = Db::query('CALL dbSearchAll(:value)', ['value' =>  $form->getFieldValue('value')]);

        $this->valResults = '';
        if (!count($results)) return;

        $this->valResults = sprintf('<p class="float-end">Results: %d</p>', count($results));
        $this->valResults .= '<table class="table table-striped table-bordered">';
        $this->valResults .= '<tr><th>Table</th><th>Column</th><th>ID</th></tr>';
        foreach ($results as $result) {
            $this->valResults .= sprintf('<tr><td>%s</td><td>%s</td><td>%s</td></tr>', $result->table, $result->column, $result->pri);
        }
        $this->valResults .= '</table>';
    }


    public function onDbSearch(Form $form, Submit $action): void
    {
        if (empty($form->getFieldValue('column'))) {
            $form->addFieldError('column', "Please provide a valid column name");
        }

        if ($form->hasErrors()) {
            Alert::addError('Form contains errors.');
            return;
        }

        $this->results = $this->dbSearchColumn(
            $form->getFieldValue('column'),
            $form->getFieldValue('value'),
            $form->getFieldValue('table'),
            truefalse($form->getFieldValue('views'))
        );

    }

    private function dbSearchColumn(string $column, string $value = '', string $table = '', bool $views = false): string
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
        $template->appendText('title', $this->getPage()->getTitle());
        $template->addCss('icon', $this->getPage()->getIcon());

        $template->appendTemplate('dbSearch', $this->dbSearch->show());
        $template->appendTemplate('dbValue', $this->dbValue->show());

        if (!empty($this->results)) {
            $template->setVisible('has-results');
            $template->setHtml('results', $this->results);
        }

        if (!empty($this->valResults)) {
            $template->setVisible('val-has-results');
            $template->setHtml('val-results', $this->valResults);
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
<div class="db-search">

  <div class="row">
    <div class="col-md-6">
      <div class="card mb-3">
        <div class="card-header"><i var="icon"></i> <span>DB Table Search</span></div>
        <div class="card-body php-info">
          <p>Use this form to search all tables for a column name or column name containing a specific value.</p>
          <div var="dbSearch"></div>
          <div choice="has-results">
            <hr>
            <div var="results"></div>
            <p>&nbsp;</p>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-6">
      <div class="card mb-3">
        <div class="card-header"><i var="icon"></i> <span>Database Value Search</span></div>
        <div class="card-body php-info">
          <p>Use this form to search all tables and columns for a specific value.</p>
          <div var="dbValue"></div>
          <div choice="val-has-results">
            <hr>
            <div var="val-results"></div>
            <p>&nbsp;</p>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}

