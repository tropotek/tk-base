<?php
namespace Bs\Console\Generator;

use Tk\Exception;
use Tk\Db;

/**
 *
 */
class ModelGenerator
{
    protected string $table     = '';
    protected string $view      = '';
    protected string $className = '';
    protected string $namespace = '';
    protected array  $tableInfo = [];


    protected function __construct(string $table, string $namespace = 'App', string $className = '')
    {
        $this->table = $this->view = $table;
        if (str_starts_with($table, 'v_')) {
            $this->table = substr($table, 2);
        }

        $namespace = trim($namespace);
        if (!$namespace)
            $namespace = 'App';
        $this->setNamespace($namespace);

        $className = trim($className);
        if (!$className) {
            $className = $this->makeClassname($this->table);
        }
        $this->setClassName($className);

        if (!Db::tableExists($this->table)) {   // Check the DB for the table
            throw new \Exception('Table `' . $this->table . '` not found in the DB `' . Db::getDbName() . '`');
        }

        // merge both info arrays to ensure we have the pri key data
        $t1 = Db::getTableInfo($this->table);
        $t2 = Db::getTableInfo($this->view);
        $this->tableInfo = array_merge($t2, $t1);
    }

    public static function create(string $table, string $namespace = 'App', string $className = ''): self
    {
        return new self($table, $namespace, $className);
    }

    protected function makeClassname(string $table): string
    {
        $classname = preg_replace_callback('/_([a-z])/i', function ($matches) {
            return strtoupper($matches[1]);
        }, $table);
        return ucfirst($classname);
    }

    protected function makePropertyName(string $colName): string
    {
        $prop = preg_replace_callback('/_([a-z])/i', function ($matches) {
            return strtoupper($matches[1]);
        }, $colName);
        return lcfirst($prop);
    }

    protected function getDefaultData(): array
    {
        $now = \Tk\Date::create();
        $primaryKey = $this->table . '_id';
        foreach ($this->tableInfo as $col => $info) {
            $info = (array)$info;
            if (($info['Key'] ?? '') == 'PRI') {
                $primaryKey = $info['Field'];
            }
        }
        return [
            'author-name'          => 'Tropotek',
            'author-biz'           => 'Tropotek',
            'author-www'           => 'http://tropotek.com.au/',
            'date'                 => $now->format(\Tk\Date::FORMAT_ISO_DATE),
            'year'                 => $now->format('Y'),
            'classname'            => $this->getClassName(),
            'name'                 => trim(preg_replace('/[A-Z]/', ' $0', $this->getClassName())),
            'table'                => $this->getTable(),
            'view'                 => $this->getView(),
            'namespace'            => $this->getNamespace(),
            'db-namespace'         => $this->getDbNamespace(),
            'table-namespace'      => $this->getTableNamespace(),
            'form-namespace'       => $this->getFormNamespace(),
            'controller-namespace' => $this->getControllerNamespace(),
            'property-name'        => lcfirst($this->getClassName()),
            'namespace-url'        => lcfirst($this->getClassName()),
            'table-id'             => str_replace('_', '-', $this->getTable()),
            'primary-col'          => $primaryKey,
            'primary-prop'         => $this->makePropertyName($primaryKey),
        ];
    }

    public function setNamespace(string $namespace): static
    {
        $this->namespace = trim($namespace, '\\');
        return $this;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function getDbNamespace(): string
    {
        return $this->namespace . '\Db';
    }

    public function getTableNamespace(): string
    {
        return $this->namespace . '\Table';
    }

    public function getFormNamespace(): string
    {
        return $this->namespace . '\Form';
    }

    public function getControllerNamespace(): string
    {
        return $this->namespace . '\Controller';
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getView(): string
    {
        return $this->view;
    }

    public function setClassName(string $className): static
    {
        $this->className = trim($className, '\\');
        return $this;
    }

    protected function tableFromClass(): string
    {
        return ltrim(strtolower(preg_replace('/[A-Z]/', '_$0', $this->getClassName())), '_');
    }

    protected function arrayMerge(array $data, array $classData = [], array $params = []): array
    {
        unset($params['namespace']);
        unset($params['classname']);
        unset($params['basepath']);
        return array_merge($data, $classData, $params);
    }


    /**
     *
     */
    public function makeModel(array $params = []): string
    {
        $tpl = $this->createModelTemplate();
        $data = $this->arrayMerge($this->getDefaultData(), $this->processModel(), $params);
        return $tpl->parse($data);
    }

    protected function processModel(): array
    {
        $data = [
            'properties' => '',
            'construct' => '',
            'validators' => '',
            'accessors' => '',
            'prepared-filter-queries' => '',
        ];
        foreach ($this->tableInfo as $col) {
            $mp = ModelProperty::create((array)$col);
            if ($mp->getName() == 'del') continue;
            $data['properties'] .= $mp->getDefinition() . "\n";

//            if ($mp->getName() != 'id')
//                $data['accessors'] .= "\n" . $mp->getAccessor() . "\n\n" . $mp->getMutator($this->getClassName()) . "\n";

            if ($mp->getType() == '\DateTime' && $mp->get('Null') == 'NO') {
                $data['construct'] .= $mp->getInitaliser() . "\n";
            }

            if (
                !$col->is_primary_key &&
                $mp->get('Null') == 'NO' &&
                $mp->get('Type') != 'text' &&
                $mp->getType() != ModelProperty::TYPE_DATE &&
                $mp->getType() != ModelProperty::TYPE_BOOL &&
                $mp->getName() != 'id' &&
                $mp->getName() != 'orderBy'
            ) {
                $data['validators'] .= "\n" . $mp->getValidation() . "\n";
            }

            if ($mp->getType() != ModelProperty::TYPE_DATE && $mp->get('Type') != 'text') {
                $data['prepared-filter-queries'] .= $mp->getPreparedFilterQuery() . "\n";
            }
        }
        $data['construct'] = rtrim($data['construct'], "\n");
        return $data;
    }

    protected function createModelTemplate(): \Tk\CurlyTemplate
    {
        // ------ TEMPLATE ------
        $classTpl = <<<STR
<?php
namespace {db-namespace};

use Tk\Db\Model;
use Tk\Db;
use Tk\Db\Filter;

class {classname} extends Model
{
{properties}

    public function __construct()
    {
{construct}
    }

    public function save(): void
    {
        \$map = static::getDataMap();

        \$values = \$map->getArray(\$this);
        if (\$this->{primary-prop}) {
            \$values['{primary-col}'] = \$this->{primary-prop};
            Db::update('{table}', '{primary-col}', \$values);
        } else {
            unset(\$values['{primary-col}']);
            Db::insert('{table}', \$values);
            \$this->{primary-prop} = Db::getLastInsertId();
        }

        \$this->reload();
    }

    public static function find(int \${primary-prop}): ?self
    {
        return Db::queryOne("
            SELECT *
            FROM {view}
            WHERE {primary-col} = :{primary-prop}",
            compact('{primary-prop}'),
            self::class
        );
    }

    /**
     * @return array<int,{classname}>
     */
    public static function findAll(): array
    {
        return Db::query("
            SELECT *
            FROM {view}",
            [],
            self::class
        );
    }

    /**
     * @return array<int,{classname}>
     */
    public static function findFiltered(array|Filter \$filter): array
    {
        \$filter = Filter::create(\$filter);
        \$filter->appendFrom('{view} a');

        if (!empty(\$filter['search'])) {
            \$filter['lSearch'] = '%' . \$filter['search'] . '%';
            \$w  = 'a.{primary-col} = :search';
            // \$w .= 'OR LOWER(a.name) LIKE LOWER(:lSearch)';
            if (\$w) \$filter->appendWhere('AND (%s)', \$w);
        }

        if (!empty(\$filter['id'])) {
            \$filter['{primary-prop}'] = \$filter['id'];
        }
        if (!empty(\$filter['{primary-prop}'])) {
            if (!is_array(\$filter['{primary-prop}'])) \$filter['{primary-prop}'] = [\$filter['{primary-prop}']];
            \$filter->appendWhere('AND a.{primary-col} IN :{primary-prop}');
        }

        if (!empty(\$filter['exclude'])) {
            if (!is_array(\$filter['exclude'])) \$filter['exclude'] = [\$filter['exclude']];
            \$filter->appendWhere('AND a.{primary-col} NOT IN :exclude');
        }
{prepared-filter-queries}
        return Db::query("
            SELECT *
            FROM {\$filter->getSql()}",
            \$filter->all(),
            self::class
        );
    }

    public function validate(): array
    {
        \$errors = [];
{validators}
        return \$errors;
    }

}
STR;
        return \Tk\CurlyTemplate::create($classTpl);
    }

    public function makeManager(array $params = []): string
    {
        $tpl = $this->createManagerTemplate();
        $data = $this->arrayMerge($this->getDefaultData(), $this->processTable('table'), $params);
        return $tpl->parse($data);
    }

    protected function processTable(string $tableProperty = ''): array
    {
        $data = [
            'cell-list' => ''
        ];
        $default = $this->getDefaultData();
        foreach ($this->tableInfo as $col) {
            $mp = ModelProperty::create((array)$col);
            if ($mp->getName() == 'del') continue;
            if ($mp->get('Type') != 'text')
                $data['cell-list'] .= $mp->getTableCell($this->getClassName(), $this->getDbNamespace(), $default['primary-prop'], $tableProperty) . "\n";
        }
        return $data;
    }

    protected function createManagerTemplate(): \Tk\CurlyTemplate
    {
        // ------ TEMPLATE ------
        $classTpl = <<<PHP
<?php
namespace {controller-namespace}\{classname};

use {db-namespace}\{classname};
use App\Db\User;
use Bs\Mvc\ControllerAdmin;
use Bs\Mvc\Table;
use Dom\Template;
use Tk\Form\Field\Input;
use Tk\Table\Cell;
use Tk\Table\Cell\RowSelect;
use Tk\Table\Action\Csv;
use Tk\Table\Action\Delete;
use Tk\Table\Action\Select;
use Tk\Uri;
use Tk\Db;

class Manager extends ControllerAdmin
{
    protected ?Table \$table = null;

    public function doDefault(): void
    {
        //\$this->setUserAccess(User::PERM_SYSADMIN);
        \$this->getPage()->setTitle('{name} Manager', 'fa fa-cogs');

        // init table
        \$this->table = new Table('{table-id}');
        \$this->table->setOrderBy('{primary-col}');
        \$this->table->setLimit(25);

        \$rowSelect = RowSelect::create('id', '{primary-prop}');
        \$this->table->appendCell(\$rowSelect);

        \$this->table->appendCell('actions')
            ->addCss('text-nowrap text-center')
            ->addOnValue(function({classname} \$obj, Cell \$cell) {
                \$url = Uri::create('/{namespace-url}Edit')->set('{primary-prop}', \$obj->{primary-prop});
                return <<<HTML
                    <a class="btn btn-outline-success" href="\$url" title="Edit"><i class="fa fa-fw fa-edit"></i></a>
                HTML;
            });

{cell-list}
        // Add Filter Fields
        \$this->table->getForm()->appendField(new Input('search'))
            ->setAttr('placeholder', 'Search');


        // Add Table actions
        \$this->table->appendAction(Delete::create()
            ->addOnGetSelected([\$rowSelect, 'getSelected'])
            ->addOnDelete(function(Delete \$action, array \$selected) {
                foreach (\$selected as \${primary-col}) {
                    Db::delete('{table}', compact('{primary-col}'));
                }
            }));

        \$this->table->appendAction(Select::create('Active Status', 'fa fa-fw fa-times')
            ->setActions(['Active' => 'active', 'Disable' => 'disable'])
            ->setConfirmStr('Toggle active/disable on the selected rows?')
            ->addOnGetSelected([\$rowSelect, 'getSelected'])
            ->addOnSelect(function(Select \$action, array \$selected, string \$value) {
                foreach (\$selected as \$id) {
                    \$obj = {classname}::find(\$id);
                    \$obj->active = (strtolower(\$value) == 'active');
                    \$obj->save();
                }
            })
        );

        \$this->table->appendAction(Csv::create()
            ->addOnCsv(function(Csv \$action) {
                \$action->setExcluded(['actions']);
                if (!\$this->table->getCell({classname}::getPrimaryProperty())) {
                    \$this->table->prependCell({classname}::getPrimaryProperty())->setHeader('id');
                }
                //\$this->table->getCell('name')->getOnValue()->reset();
                \$filter = \$this->table->getDbFilter()->resetLimits();
                return {classname}::findFiltered(\$filter);
            }));

        // execute table
        \$this->table->execute();

        // todo: remove cell orderBy validation before release
        // if (!\$this->table->validateCells({classname}::getDataMap())) {
        //     \$this->table->getTableSession()->remove(\$this->table->makeRequestKey(Table::PARAM_ORDERBY));
        // }

        // Set the table rows
        \$filter = \$this->table->getDbFilter();
        \$rows = {classname}::findFiltered(\$filter);
        \$this->table->setRows(\$rows, Db::getLastStatement()->getTotalRows());
    }

    public function show(): ?Template
    {
        \$template = \$this->getTemplate();
        \$template->setText('title', \$this->getPage()->getTitle());
        \$template->addCss('icon', \$this->getPage()->getIcon());

        \$template->appendTemplate('content', \$this->table->show());

        return \$template;
    }

    public function __makeTemplate(): ?Template
    {
        \$html = <<<HTML
<div>
  <div class="page-actions card mb-3">
    <div class="card-body">
      <a href="/{namespace-url}Edit" title="Create {name}" class="btn btn-outline-secondary"><i class="fa fa-plus"></i> Create {name}</a>
    </div>
  </div>
  <div class="card mb-3">
    <div class="card-header"><i var="icon"></i> <span var="title"></span></div>
    <div class="card-body" var="content"></div>
  </div>
</div>
HTML;
        return Template::load(\$html);
    }

}
PHP;
        return \Tk\CurlyTemplate::create($classTpl);
    }


    /**
     *
     */
    public function makeTable(array $params = []): string
    {
        $tpl = $this->createTableTemplate();
        $data = $this->arrayMerge($this->getDefaultData(), $this->processTable(), $params);
        return $tpl->parse($data);
    }

    protected function createTableTemplate(): \Tk\CurlyTemplate
    {
        // ------ TEMPLATE ------
        $classTpl = <<<PHP
<?php
namespace {table-namespace};

use Bs\Mvc\Table;
use Dom\Template;
use Tk\Alert;
use Tk\Uri;
use Tk\Db;
use Tk\Table\Action\Csv;
use Tk\Table\Action\Delete;
use Tk\Form\Field\Input;
use Tk\Table\Cell;
use Tk\Table\Cell\RowSelect;

/**
 * Example Controller:
 * <code>
 * class Manager extends \Bs\ControllerAdmin {
 *      protected ?Table \$table = null;
 *      public function doDefault(mixed \$request, string \$type): void
 *      {
 *          ...
 *          // init the user table
 *          \$this->table = new \{table-namespace}\{classname}();
 *          \$this->table->setOrderBy('name');
 *          \$this->table->setLimit(25);
 *          \$this->table->execute();
 *          // Set the table rows
 *          \$filter = \$this->table->getDbFilter();
 *          \$rows = User::findFiltered(\$filter);
 *          \$this->table->setRows(\$rows, Db::getLastStatement()->getTotalRows());
 *          ...
 *      }
 *      public function show(): ?Template
 *      {
 *          \$template = \$this->getTemplate();
 *          \$template->appendTemplate('content', \$this->table->show());
 *          return \$template;
 *      }
 * }
 * </code>
 */
class {classname} extends Table
{

    public function init(): static
    {
        \$rowSelect = RowSelect::create('id', '{primary-prop}');
        \$this->appendCell(\$rowSelect);

        \$this->appendCell('actions')
            ->addCss('text-nowrap text-center')
            ->addOnValue(function(\{db-namespace}\{classname} \$obj, Cell \$cell) {
                \$url = Uri::create('/{namespace-url}Edit')->set('{primary-prop}', \$obj->{primary-prop});
                return <<<HTML
                    <a class="btn btn-outline-success" href="\$url" title="Edit"><i class="fa fa-fw fa-edit"></i></a>
                HTML;
            });
{cell-list}
        // Add Filter Fields
        \$this->getForm()->appendField(new Input('search'))
            ->setAttr('placeholder', 'Search');

        // Add Table actions
        \$this->appendAction(Delete::create())
            ->addOnGetSelected([\$rowSelect, 'getSelected'])
            ->addOnDelete(function(Delete \$action, array \$selected) {
                foreach (\$selected as \${primary-col}) {
                    Db::delete('{table}', compact('{primary-col}'));
                }
            });

        \$this->appendAction(Csv::create()
            ->addOnCsv(function(Csv \$action) {
                \$action->setExcluded(['actions']);
                if (!\$this->table->getCell({classname}::getPrimaryProperty())) {
                    \$this->table->prependCell({classname}::getPrimaryProperty())->setHeader('id');
                }
                //\$this->table->getCell('name')->getOnValue()->reset();
                \$filter = \$this->table->getDbFilter()->resetLimits();
                return {classname}::findFiltered(\$filter);
            }));

        return \$this;
    }

    public function show(): ?Template
    {
        \$renderer = \$this->getRenderer();

        return parent::show();
    }
}
PHP;
        return \Tk\CurlyTemplate::create($classTpl);
    }

    /**
     * @throws \Exception
     */
    public function makeForm(array $params = []): string
    {
        $tpl = $this->createFormTemplate();
        $data = $this->arrayMerge($this->getDefaultData(), $this->processForm(), $params);
        return $tpl->parse($data);
    }

    /**
     * @throws \Exception
     */
    public function makeEdit(array $params = []): string
    {
        $tpl = $this->createFormEditTemplate();
        $data = $this->arrayMerge($this->getDefaultData(), $this->processForm('form'), $params);
        return $tpl->parse($data);
    }

    protected function processForm(string $formProperty = ''): array
    {
        $data = [
            'field-list' => ''
        ];
        foreach ($this->tableInfo as $col) {
            $mp = ModelProperty::create((array)$col);
            if ($mp->getName() == 'del' || $mp->getName() == 'modified' || $mp->getName() == 'created' || $mp->getName() == 'id') continue;
            $data['field-list'] .= $mp->getFormField($this->getClassName(), $this->getNamespace(), $formProperty) . "\n";
        }
        return $data;
    }

    protected function createFormEditTemplate(): \Tk\CurlyTemplate
    {
        // ------ TEMPLATE ------
        $classTpl = <<<STR
<?php
namespace {controller-namespace}\{classname};

use {db-namespace}\{classname};
use App\Db\User;
use Bs\Mvc\ControllerAdmin;
use Bs\Mvc\Form;
use Dom\Template;
use Tk\Alert;
use Tk\Date;
use Tk\Exception;
use Tk\Form\Action\Link;
use Tk\Form\Action\SubmitExit;
use Tk\Form\Action\Submit;
use Tk\Form\Field\Checkbox;
use Tk\Form\Field\Textarea;
use Tk\Form\Field\Hidden;
use Tk\Form\Field\Input;
use Tk\Form\Field\Select;
use Tk\Uri;

class Edit extends ControllerAdmin
{
    protected ?{classname} \${property-name} = null;
    protected ?Form  \$form = null;


    public function doDefault(): void
    {
        //\$this->setUserAccess(User::PERM_SYSADMIN);
        \$this->getPage()->setTitle('Edit {name}', 'fa fa-edit');

        \${primary-prop} = intval(\$_GET['{primary-prop}'] ?? 0);

        \$this->{property-name} = new {classname}();
        if (\${primary-prop}) {
            \$this->{property-name} = {classname}::find(\${primary-prop});
            if (!(\$this->{property-name} instanceof {classname})) {
                throw new Exception("invalid {primary-prop} \${primary-prop}");
            }
        }

        // Get the form template
        \$this->form = new Form();
{field-list}
        \$this->form->appendField(new SubmitExit('save', [\$this, 'onSubmit']));
        \$this->form->appendField(new Link('cancel', Uri::create('/{namespace-url}Manager')));

        \$load = \$this->{property-name}->unmapForm();
        \$this->form->setFieldValues(\$load);

        \$this->form->execute(\$_POST);
    }

    public function onSubmit(Form \$form, Submit \$action): void
    {
        \$values = \$form->getFieldValues();
        \$this->{property-name}->mapForm(\$values);

        \$form->addFieldErrors(\$this->{property-name}->validate());
        if (\$form->hasErrors()) {
            return;
        }

        \$isNew = (\$this->{property-name}->{primary-prop} == 0);
        \$this->{property-name}->save();

        Alert::addSuccess('Form save successfully.');
        \$action->setRedirect(Uri::create()->set('{primary-prop}', \$this->{property-name}->{primary-prop}));
        if (\$form->getTriggeredAction()->isExit()) {
            \$action->setRedirect(\$this->getBackUrl());
        }
    }

    public function show(): ?Template
    {
        \$template = \$this->getTemplate();

        \$template->setText('title', \$this->getPage()->getTitle());
        \$template->addCss('icon', \$this->getPage()->getIcon());

        if (\$this->{property-name}->{primary-prop}) {
            \$template->setText('modified', \$this->{property-name}->modified->format(Date::FORMAT_LONG_DATETIME));
            \$template->setText('created', \$this->{property-name}->created->format(Date::FORMAT_LONG_DATETIME));
            \$template->setVisible('edit');
        }

        \$template->appendTemplate('content', \$this->form->show());

        return \$template;
    }

    public function __makeTemplate(): ?Template
    {
        \$html = <<<HTML
<div>
  <div class="page-actions card mb-3">
    <div class="card-body">
      <a href="/" title="Back" class="btn btn-outline-secondary" var="back"><i class="fa fa-arrow-left"></i> Back</a>
    </div>
  </div>
  <div class="card mb-3">
    <div class="card-header">
      <i var="icon"></i> <span var="title"></span>
      <div class="info-dropdown dropdown" title="Details" choice="edit">
        <a href="#" class="dropdown-toggle arrow-none card-drop" data-bs-toggle="dropdown" aria-expanded="false"><i class="mdi mdi-dots-vertical"></i></a>
        <div class="dropdown-menu dropdown-menu-end">
          <p class="dropdown-item"><span class="d-inline-block">Modified:</span> <span var="modified">...</span></p>
          <p class="dropdown-item"><span class="d-inline-block">Created:</span> <span var="created">...</span></p>
        </div>
      </div>
    </div>
    <div class="card-body" var="content"></div>
  </div>
</div>
HTML;
        return Template::load(\$html);
    }

}
STR;
        return \Tk\CurlyTemplate::create($classTpl);
    }

    protected function createFormTemplate(): \Tk\CurlyTemplate
    {
        // ------ TEMPLATE ------
        $classTpl = <<<PHP
<?php
namespace {form-namespace};

use Bs\Mvc\ControllerAdmin;
use Bs\Mvc\Form;
use Dom\Template;
use Tk\Alert;
use Tk\Exception;
use Tk\Form\Action\Link;
use Tk\Form\Action\SubmitExit;
use Tk\Form\Action\Submit;
use Tk\Form\Field\Checkbox;
use Tk\Form\Field\Textarea;
use Tk\Form\Field\Hidden;
use Tk\Form\Field\Input;
use Tk\Form\Field\Select;
use Tk\Uri;

/**
 * Example Controller:
 * <code>
 * class Edit extends \Bs\ControllerAdmin {
 *      protected ?\Bs\Form \$form = null;
 *      public function doDefault(mixed \$request, string \$type): void
 *      {
 *          ...
 *          \$this->form = new \Bs\Form\{classname}(\$this->get{classname}());
 *          \$this->form->execute(\$_POST);
 *          ...
 *      }
 *      public function show(): ?Template
 *      {
 *          \$template = \$this->getTemplate();
 *          \$template->appendTemplate('content', \$this->form->show());
 *          return \$template;
 *      }
 * }
 * </code>
 */
class {classname} extends Form
{

    public function init(): static
    {
{field-list}
        \$this->appendField(new SubmitExit('save', [\$this, 'onSubmit']));
        \$this->appendField(new Link('cancel', \$this->getBackUrl()));

        return \$this;
    }

    public function execute(array \$values = []): static
    {
        \$this->init();

        // Load form with object values
        \$load = \$this->get{classname}()->unmapForm();
        \$this->setFieldValues(\$load);

        parent::execute(\$values);
        return \$this;
    }

    public function onSubmit(Form \$form, Submit \$action): void
    {
        \$values = \$form->getFieldValues();
        \$this->get{classname}()->mapForm(\$values);

        \$form->addFieldErrors(\$this->get{classname}()->validate());
        if (\$form->hasErrors()) {
            return;
        }

        \$isNew = (\$this->get{classname}()->{primary-prop} == 0);
        \$this->get{classname}()->save();

        Alert::addSuccess('Form save successfully.');
        \$action->setRedirect(Uri::create()->set('{primary-prop}', \$this->get{classname}()->{primary-prop}));
        if (\$form->getTriggeredAction()->isExit()) {
            \$action->setRedirect(\$this->getBackUrl());
        }
    }

    public function show(): ?Template
    {
        \$renderer = \$this->getRenderer();

        return \$renderer->show();
    }


    public function get{classname}(): ?\{db-namespace}\{classname}
    {
        /** @var \{db-namespace}\{classname} \$obj */
        \$obj = \$this->getModel();
        return \$obj;
    }

}
PHP;
        return \Tk\CurlyTemplate::create($classTpl);
    }


}
