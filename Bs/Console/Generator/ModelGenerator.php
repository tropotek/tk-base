<?php
namespace Bs\Console\Generator;

use Tk\Exception;
use Tk\Db;

/**
 * @todo Update to use the new \Tk\Db\Model system
 *     -
 */
class ModelGenerator
{
    protected string $table     = '';
    protected string $view      = '';
    protected string $className = '';
    protected string $namespace = '';
    protected array  $tableInfo = [];


    /**
     * @throws \Exception
     */
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

    /**
     * @throws \Exception
     */
    public static function create(string $table, string $namespace = 'App', string $className = ''): ModelGenerator
    {
        return new static($table, $namespace, $className);
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
            'namespace-url'        => str_replace('_', '/', $this->getTable()),
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
        return $data;
    }

    protected function createModelTemplate(): \Tk\CurlyTemplate
    {
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

        if (!empty(\$filter['search'])) {
            \$filter['search'] = '%' . \$filter['search'] . '%';
            \$w = '';
            //\$w .= 'LOWER(a.name) LIKE LOWER(:search) OR ';
            \$w .= 'a.{primary-col} = :search OR ';
            if (is_numeric(\$filter['search'])) {
                \$w .= 'a.{primary-col} = :search OR ';
            }
            if (\$w) \$filter->appendWhere('(%s) AND ', substr(\$w, 0, -3));
        }

        if (!empty(\$filter['id'])) {
            \$filter['{primary-prop}'] = \$filter['id'];
        }
        if (!empty(\$filter['{primary-prop}'])) {
            if (!is_array(\$filter['{primary-prop}'])) \$filter['{primary-prop}'] = [\$filter['{primary-prop}']];
            \$filter->appendWhere('a.{primary-col} IN :{primary-prop} AND ');
        }

        if (!empty(\$filter['exclude'])) {
            if (!is_array(\$filter['exclude'])) \$filter['exclude'] = [\$filter['exclude']];
            \$filter->appendWhere('a.{primary-col} NOT IN :exclude AND ', \$filter['exclude']);
        }
{prepared-filter-queries}
        return Db::query("
            SELECT *
            FROM {view} a
            {\$filter->getSql()}",
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


    /**
     *
     */
    public function makeManager(array $params = []): string
    {
        $tpl = $this->createTableManagerTemplate();
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

    protected function createTableManagerTemplate(): \Tk\CurlyTemplate
    {
        $classTpl = <<<PHP
<?php
namespace {controller-namespace}\{classname};

use {db-namespace}\{classname};
use Bs\ControllerAdmin;
use Bs\Table;
use Dom\Template;
use Tk\Form\Field\Input;
use Tk\Table\Action\Csv;
use Tk\Table\Cell;
use Tk\Table\Cell\RowSelect;
use Tk\Table\Action\Delete;
use Tk\Uri;
use Tk\Db;

/**
 *
 */
class Manager extends ControllerAdmin
{
    protected ?Table \$table = null;

    public function doDefault(): void
    {
        \$this->getPage()->setTitle('{name} Manager');

        // init table
        \$this->table = new \Bs\Table();
        \$this->table->setOrderBy('{primary-col}');
        \$this->table->setLimit(25);

        \$rowSelect = RowSelect::create('id', 'userId');
        \$this->table->appendCell(\$rowSelect);

        \$this->table->appendCell('actions')
            ->addCss('text-nowrap text-center')
            ->addOnValue(function({classname} \${name}, Cell \$cell) {
                \$url = Uri::create('/{namespace-url}Edit')->set('{primary-prop}', \${name}->{primary-prop});
                return <<<HTML
                    <a class="btn btn-outline-success" href="\$url" title="Edit"><i class="fa fa-fw fa-edit"></i></a>
                HTML;
            });
{cell-list}
        // Add Filter Fields
        \$this->table->getForm()->appendField(new Input('search'))
            ->setAttr('placeholder', 'Search');

        // init filter fields for actions to access to the filter values
        \$this->table->initForm();

        // Add Table actions
        \$this->table->appendAction(Delete::create(\$rowSelect))
            ->addOnDelete(function(Delete \$action, array \$selected) {
                foreach (\$selected as \${primary-col}) {
                    Db::delete('{table}', compact('{primary-col}'));
                }
            });

        \$this->table->appendAction(Csv::create(\$rowSelect))
            ->addOnCsv(function(Csv \$action, array \$selected) {
                \$action->setExcluded(['id', 'actions']);
                \$filter = \$this->table->getDbFilter();
                if (\$selected) {
                    \$rows = {classname}::findFiltered(\$filter);
                } else {
                    \$rows = {classname}::findFiltered(\$filter->resetLimits());
                }
                return \$rows;
            });

        \$this->table->execute();

        // Set the table rows
        \$filter = \$this->table->getDbFilter();
        \$rows = {classname}::findFiltered(\$filter);
        \$this->table->setRows(\$rows, Db::getLastStatement()->getTotalRows());
    }

    public function show(): ?Template
    {
        \$template = \$this->getTemplate();
        \$template->setText('title', \$this->getPage()->getTitle());
        \$template->setAttr('back', 'href', \$this->getBackUrl());

        \$template->appendTemplate('content', \$this->table->show());

        return \$template;
    }

    public function __makeTemplate(): ?Template
    {
        \$html = <<<HTML
<div>
  <div class="page-actions card mb-3">
    <div class="card-header"><i class="fa fa-cogs"></i> Actions</div>
    <div class="card-body" var="actions">
      <a href="/" title="Back" class="btn btn-outline-secondary" var="back"><i class="fa fa-arrow-left"></i> Back</a>
      <a href="#" title="Create {name}" class="btn btn-outline-secondary" var="create"><i class="fa fa-plus"></i> Create {name}</a>
    </div>
  </div>
  <div class="card mb-3">
    <div class="card-header" var="title"><i class="fa fa-cogs"></i> </div>
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
        $classTpl = <<<PHP
<?php
namespace {table-namespace};

use Bs\Table;
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
        \$editUrl = Uri::create('/{namespace-url}Edit');

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

        // init filter fields for actions to access to the filter values
        \$this->initForm();

        // Add Table actions
        \$this->appendAction(Delete::create(\$rowSelect))
            ->addOnDelete(function(Delete \$action, array \$selected) {
                foreach (\$selected as \${primary-col}) {
                    Db::delete('{table}', compact('{primary-col}'));
                }
            });

        \$this->appendAction(Csv::create(\$rowSelect))
            ->addOnCsv(function(Csv \$action, array \$selected) {
                \$action->setExcluded(['id', 'actions']);
                \$filter = \$this->getDbFilter();
                if (\$selected) {
                    \$rows = \{db-namespace}\{classname}::findFiltered(\$filter);
                } else {
                    \$rows = \{db-namespace}\{classname}::findFiltered(\$filter->resetLimits());
                }
                return \$rows;
            });

        return \$this;
    }

    public function show(): ?Template
    {
        \$renderer = \$this->getRenderer();
        return \$renderer->show();
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
        $classTpl = <<<STR
<?php
namespace {controller-namespace}\{classname};

use {db-namespace}\{classname};
use Bs\ControllerAdmin;
use Bs\Factory;
use Bs\Form;
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

class Edit extends ControllerAdmin
{
    protected ?{classname} \${property-name} = null;
    protected ?Form  \$form = null;


    public function doDefault(): void
    {
        \$this->getPage()->setTitle('Edit {name}');

        \${primary-prop} = intval(\$_GET['{primary-prop}'] ?? 0);

        \$this->{property-name} = new {classname}();
        if (\${primary-prop}) {
            \$this->{property-name} = {classname}::find(\${primary-prop});
            if (!(\$this->{property-name} instanceof {classname})) {
                throw new Exception("invalid {primary-prop} \${primary-prop}");
            }
        }

        // todo: \$this->setAccess(...);

        // Get the form template
        \$this->form = new Form();
{field-list}
        \$this->form->appendField(new SubmitExit('save', [\$this, 'onSubmit']));
        \$this->form->appendField(new Link('cancel', Uri::create('/{property-name}Manager')));

        \$load = \$this->form->unmapModel(\$this->{property-name});
        \$this->form->setFieldValues(\$load);

        \$this->form->execute(\$_POST);

    }

    public function onSubmit(Form \$form, Submit \$action): void
    {
        \$form->mapModel(\$this->{property-name});

        \$form->addFieldErrors(\$this->{property-name}->validate());
        if (\$form->hasErrors()) {
            return;
        }

        \$isNew = (\$this->{property-name}->{primary-prop} == 0);
        \$this->{property-name}->save();

        Alert::addSuccess('Form save successfully.');
        \$action->setRedirect(Uri::create()->set('{primary-prop}', \$this->{property-name}->{primary-prop}));
        if (\$form->getTriggeredAction()->isExit()) {
            \$action->setRedirect(Factory::instance()->getBackUrl());
        }
    }

    public function show(): ?Template
    {
        // Setup field group widths with bootstrap classes
        //\$this->form->getField('username')->addFieldCss('col-6');
        //\$this->form->getField('email')->addFieldCss('col-6');

        \$template = \$this->getTemplate();
        \$template->setText('title', \$this->getPage()->getTitle());
        \$template->setAttr('back', 'href', Factory::instance()->getBackUrl());

        \$template->appendTemplate('content', \$this->form->show());

        return \$template;
    }

    public function __makeTemplate(): ?Template
    {
        \$html = <<<HTML
<div>
  <div class="page-actions card mb-3">7
    <div class="card-header"><i class="fa fa-cogs"></i> Actions</div>
    <div class="card-body" var="actions">
      <a href="/" title="Back" class="btn btn-outline-secondary" var="back"><i class="fa fa-arrow-left"></i> Back</a>
    </div>
  </div>
  <div class="card mb-3">
    <div class="card-header" var="title"><i class="fa fa-users"></i> </div>
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
        $classTpl = <<<PHP
<?php
namespace {form-namespace};

use Bs\ControllerAdmin;
use Bs\Factory;
use Bs\Form;
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
        \$this->appendField(new Link('cancel', Factory::instance()->getBackUrl()));

        return \$this;
    }

    public function execute(array \$values = []): static
    {
        \$this->init();

        // Load form with object values
        \$load = \$this->unmapModel(\$this->get{classname}());
        \$this->setFieldValues(\$load);

        parent::execute(\$values);
        return \$this;
    }

    public function onSubmit(Form \$form, Submit \$action): void
    {
        \$form->mapModel(\$this->get{classname}());

        \$form->addFieldErrors(\$this->get{classname}()->validate());
        if (\$form->hasErrors()) {
            return;
        }

        \$isNew = (\$this->get{classname}()->{primary-prop} == 0);
        \$this->get{classname}()->save();

        Alert::addSuccess('Form save successfully.');
        \$action->setRedirect(Uri::create()->set('{primary-prop}', \$this->get{classname}()->{primary-prop}));
        if (\$form->getTriggeredAction()->isExit()) {
            \$action->setRedirect(Factory::instance()->getBackUrl());
        }
    }

    public function show(): ?Template
    {
        // Setup field group widths with bootstrap classes
        //\$this->getField('username')->addFieldCss('col-6');
        //\$this->getField('email')->addFieldCss('col-6');

        \$renderer = \$this->getRenderer();
        \$renderer?->addFieldCss('mb-3');

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
