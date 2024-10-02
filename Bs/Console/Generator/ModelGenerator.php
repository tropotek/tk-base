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
    public function makeTable(array $params = []): string
    {
        $tpl = $this->createTableTemplate();
        $data = $this->arrayMerge($this->getDefaultData(), $this->processTable(), $params);
        return $tpl->parse($data);
    }

    /**
     *
     */
    public function makeManager(array $params = []): string
    {
        $tpl = $this->createTableManagerTemplate();
        $data = $this->getDefaultData();
        return $tpl->parse($data);
    }

    protected function processTable(): array
    {
        $data = [
            'cell-list' => ''
        ];
        foreach ($this->tableInfo as $col) {
            $mp = ModelProperty::create((array)$col);
            if ($mp->getName() == 'del') continue;
            if ($mp->get('Type') != 'text')
                $data['cell-list'] .= $mp->getTableCell($this->getClassName(), $this->getNamespace()) . "\n";
        }
        return $data;
    }

    protected function createTableManagerTemplate(): \Tk\CurlyTemplate
    {
        $classTpl = <<<PHP
<?php
namespace {controller-namespace}\{classname};

use Bs\PageController;
use Bs\Table\ManagerTrait;
use Dom\Template;
use Bs\Db\User;
use Symfony\Component\HttpFoundation\Request;

/**
 * Add Route to /src/config/routes.php:
 * ```php
 *   \$routes->add('{table-id}-manager', '/{namespace-url}Manager')
 *       ->controller([{controller-namespace}\{classname}\Manager::class, 'doDefault']);
 * ```
 */
class Manager extends PageController
{
    use ManagerTrait;

    public function __construct()
    {
        parent::__construct(\$this->getFactory()->getAdminPage());
        \$this->getPage()->setTitle('{name} Manager');
        \$this->setAccess(Permissions::PERM_MANAGE_STAFF);
    }

    public function doDefault(Request \$request): \App\Page|\Dom\Mvc\Page
    {
        \$this->setTable(new \{table-namespace}\{classname}());
        \$this->getTable()->init();
        \$this->getTable()->findList([], \$this->getTable()->getTool());
        \$this->getTable()->execute(\$request);

        return \$this->getPage();
    }

    public function show(): ?Template
    {
        \$template = \$this->getTemplate();
        \$template->setText('title', \$this->getPage()->getTitle());
        \$template->setAttr('create', 'href', \$this->getBackUrl());

        \$template->appendTemplate('content', \$this->getTable()->show());

        return \$template;
    }

    public function __makeTemplate(): ?Template
    {
        \$html = <<<HTML
<div>
  <div class="card mb-3">
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
        return \$this->loadTemplate(\$html);
    }

}
PHP;
        return \Tk\CurlyTemplate::create($classTpl);
    }

    protected function createTableTemplate(): \Tk\CurlyTemplate
    {
        $classTpl = <<<PHP
<?php
namespace {table-namespace};

use {db-namespace}\{classname}Map;
use Dom\Template;
use Bs\Table\ManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Tk\Db\Mapper\Result;
use Tk\Form\Field;
use Tk\Table\Cell;
use Tk\Table\Action;
use Tk\Db\Tool;
use Tk\Uri;

class {classname} extends ManagerInterface
{

    public function initCells(): void
    {
        \$editUrl = Uri::create('/{namespace-url}Edit');

{cell-list}

        // Filters
        \$this->getFilterForm()->appendField(new Field\Input('search'))->setAttr('placeholder', 'Search');

        // Actions
        //\$this->appendAction(new Action\Button('Create'))->setUrl(\$editUrl);
        \$this->appendAction(new Action\Delete());
        \$this->appendAction(new Action\Csv())->addExcluded('actions');

    }

    public function execute(Request \$request): static
    {
        parent::execute(\$request);
        return \$this;
    }

    public function findList(array \$filter = [], ?Tool \$tool = null): null|array|Result
    {
        if (!\$tool) \$tool = \$this->getTool();
        \$filter = array_merge(\$this->getFilterForm()->getFieldValues(), \$filter);
        \$list = {classname}Map::create()->findFiltered(\$filter, \$tool);
        \$this->setList(\$list);
        return \$list;
    }

    public function show(): ?Template
    {
        \$renderer = \$this->getTableRenderer();
        \$this->getRow()->addCss('text-nowrap');
        \$this->showFilterForm();
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
//        if (!empty($params['modelForm']))
//            $tpl = $this->createModelFormTemplate();
        $data = $this->arrayMerge($this->getDefaultData(), $this->processForm(!empty($params['modelForm'])), $params);
        return $tpl->parse($data);
    }

    /**
     * @throws \Exception
     */
    public function makeEdit(array $params = []): string
    {
        $tpl = $this->createFormEditTemplate();
        $data = $this->getDefaultData();
        return $tpl->parse($data);
    }

    protected function processForm(bool $isModelForm = false): array
    {
        $data = [
            'field-list' => ''
        ];
        foreach ($this->tableInfo as $col) {
            $mp = ModelProperty::create((array)$col);
            if ($mp->getName() == 'del' || $mp->getName() == 'modified' || $mp->getName() == 'created' || $mp->getName() == 'id') continue;
            $data['field-list'] .= $mp->getFormField($this->getClassName(), $this->getNamespace(), $isModelForm) . "\n";
        }
        return $data;
    }

    protected function createFormEditTemplate(): \Tk\CurlyTemplate
    {
        $classTpl = <<<STR
<?php
namespace {controller-namespace}\{classname};

use {db-namespace}\{classname};
use {db-namespace}\{classname}Map;
use Bs\Form\EditTrait;
use Bs\PageController;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Bs\Db\User;
use Tk\Exception;

/**
 * Add Route to /src/config/routes.php:
 * ```php
 *   \$routes->add('{table-id}-manager', '/{namespace-url}Edit')
 *       ->controller([{controller-namespace}\{classname}\Edit::class, 'doDefault']);
 * ```
 */
class Edit extends PageController
{
    use EditTrait;

    protected ?{classname} \${property-name} = null;


    public function __construct()
    {
        parent::__construct(\$this->getFactory()->getAdminPage());
        \$this->getPage()->setTitle('Edit {name}');
        \$this->setAccess(Permissions::PERM_ADMIN);
    }

    public function doDefault(Request \$request): \App\Page|\Dom\Mvc\Page
    {
        \$this->{property-name} = new {classname}();
        if (\$request->query->getInt('{primary-prop}')) {
            \$this->{property-name} = {classname}Map::create()->find(\$request->query->getInt('{primary-prop}'));
        }
        if (!\$this->{property-name}) {
            throw new Exception('Invalid {classname} ID: ' . \$request->query->getInt('{primary-prop}'));
        }

        \$this->setForm(new \{form-namespace}\{classname}(\$this->{property-name}));
        \$this->getForm()->init()->execute(\$request->request->all());

        return \$this->getPage();
    }

    public function show(): ?Template
    {
        \$template = \$this->getTemplate();
        \$template->setText('title', \$this->getPage()->getTitle());
        \$template->setAttr('back', 'href', \$this->getBackUrl());

        \$template->appendTemplate('content', \$this->form->show());

        return \$template;
    }

    public function __makeTemplate(): ?Template
    {
        \$html = <<<HTML
<div>
  <div class="card mb-3">7
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
        return \$this->loadTemplate(\$html);
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

use Bs\Form\EditInterface;
use Dom\Template;
use Tk\Alert;
use Tk\Form;
use Tk\Form\Field;
use Tk\Form\Action;
use Tk\Uri;

class {classname} extends EditInterface
{

    protected function initFields(): void
    {
{field-list}

        \$this->getForm()->appendField(new Action\SubmitExit('save', [\$this, 'onSubmit']));
        \$this->getForm()->appendField(new Action\Link('cancel', Uri::create('/{property-name}Manager')));

        \$load = \$this->{property-name}->getMapper()->getFormMap()->getArray(\$this->{property-name});
        \$load['id'] = \$this->{property-name}->getId();
        \$this->getForm()->setFieldValues(\$load); // Use form data mapper if loading objects

        \$this->getForm()->execute(\$request->request->all());

        \$this->setFormRenderer(new FormRenderer(\$this->getForm()));

    }

    public function execute(array \$values = []): static
    {
        \$load = \$this->get{classname}()->getMapper()->getFormMap()->getArray(\$this->get{classname}());
        \$load['{primary-prop}'] = \$this->get{classname}()->get{classname}Id();
        \$this->getForm()->setFieldValues(\$load);
        parent::execute(\$values);
        return \$this;
    }

    public function onSubmit(Form \$form, Action\ActionInterface \$action): void
    {
        \$this->{property-name}->getMapper()->getFormMap()->loadObject(\$this->{property-name}, \$form->getFieldValues());

        \$form->addFieldErrors(\$this->{property-name}->validate());
        if (\$form->hasErrors()) {
            return;
        }

        \$this->{property-name}->save();

        Alert::addSuccess('Form save successfully.');
        \$action->setRedirect(Uri::create()->set('{primary-prop}', \$this->{property-name}->get{classname}Id()));
        if (\$form->getTriggeredAction()->isExit()) {
            \$action->setRedirect(Uri::create('/{property-name}Manager'));
        }
    }

    public function show(): ?Template
    {
        // Setup field group widths with bootstrap classes
        //\$this->getForm()->getField('username')->addFieldCss('col-6');
        //\$this->getForm()->getField('email')->addFieldCss('col-6');

        \$renderer = \$this->getFormRenderer();
        \$renderer->addFieldCss('mb-3');

        return \$renderer->show();
    }


    public function get{classname}(): ?\{db-namespace}\{classname}
    {
        return \$this->getModel();
    }

}
PHP;
        return \Tk\CurlyTemplate::create($classTpl);
    }


}
