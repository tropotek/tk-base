<?php
namespace Bs\Util;



/**
 * @author Tropotek <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2017 Tropotek
 */
class ModelGenerator
{

    /**
     * @var \Tk\Db\Pdo
     */
    protected $db = null;

    /**
     * @var string
     */
    protected $table = '';

    /**
     * @var string
     */
    protected $className = '';

    /**
     * @var string
     */
    protected $namespace = 'App\Db';

    /**
     * @var array
     */
    protected $tableInfo = null;


    /**
     * @param \Tk\Db\Pdo $db
     * @param string $table
     * @throws \Exception
     */
    protected function __construct($db, $table)
    {
        $this->db = $db;
        $this->table = $table;
        if (!$db->hasTable($table)) {   // Check the DB for the table
            throw new \Exception('Table `' . $table . '` not found in the DB `' . $db->getDatabaseName().'`');
        }
        $this->className = $this->makeClassname($table);
        $this->tableInfo = $db->getTableInfo($table);
    }

    /**
     * @param \Tk\Db\Pdo $db
     * @param string $table
     * @return ModelGenerator
     * @throws \Exception
     */
    public static function create($db, $table)
    {
        $obj = new static($db, $table);
        return $obj;
    }

    /**
     * @param string $table
     * @return string
     */
    protected function makeClassname($table)
    {
        $classname = preg_replace_callback('/_([a-z])/i', function ($matches) {
            return strtoupper($matches[1]);
        }, $table);
        return ucfirst($classname);
    }

    /**
     * @return array
     */
    protected function getDefaultData()
    {
        $now = \Tk\Date::create();
        return array(
            'author-name' => 'Mick Mifsud',
            'author-biz' => 'Tropotek',
            'author-www' => 'http://tropotek.com.au/',
            'date' => $now->format(\Tk\Date::FORMAT_ISO_DATE),
            'year' => $now->format('Y'),
            'classname' => $this->getClassName(),
            'table' => $this->getTable(),
            'namespace' => $this->getNamespace()
        );
    }

    /**
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @param string $className
     * @return static
     */
    public function setClassName($className)
    {
        $this->className = $className;
        return $this;
    }

    /**
     * @param string $namespace
     * @return static
     */
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
        return $this;
    }

    /**
     * @return string
     */
    protected function tableFromClass()
    {
        return ltrim(strtolower(preg_replace('/[A-Z]/', '_$0', $this->className)), '_');
    }



    /**
     * @param array $params any overrides for the curly template
     * @return string
     * @throws \Exception
     */
    public function makeModel($params = array())
    {
        $tpl = $this->createModelTemplate();
        $data = $this->getDefaultData();
        $classData = $this->processModel();
        $data = array_merge($data, $classData, $params);
        return $tpl->parse($data);
    }

    /**
     * @return array
     */
    protected function processModel()
    {
        $data = array(
            'properties' => '',
            'construct' => '',
            'validators' => '',
            'accessors' => '',
            'mutators' => ''
        );
        foreach ($this->tableInfo as $col) {
            $mp = ModelProperty::create($col);
            if ($mp->getName() == 'del') continue;
            $data['properties'] .= "\n" . $mp->getDefinition() . "\n";
            if ($mp->getType() == '\DateTime' && $mp->get('Null') == 'NO')
                $data['construct'] .= $mp->getInitaliser() . "\n";
            if (
                $mp->get('Null') == 'NO' &&
                $mp->get('Type') != 'text' &&
                $mp->getType() != ModelProperty::TYPE_DATE &&
                $mp->getType() != ModelProperty::TYPE_BOOL &&
                $mp->getName() != 'id'
            )
                $data['validators'] .= "\n" . $mp->getValidation() . "\n";
        }
        return $data;
    }

    /**
     * @return \Tk\CurlyTemplate
     */
    protected function createModelTemplate()
    {
        $classTpl = <<<STR
<?php
namespace {namespace};

/**
 * @author {author-name}
 * @created {date}
 * @link {author-www}
 * @license Copyright {year} {author-biz}
 */
class {classname} extends \Tk\Db\Map\Model implements \Tk\ValidInterface
{
{properties}

    /**
     * {classname}
     */
    public function __construct()
    {
{construct}
    }
    
{accessors}
{mutators}
    
    /**
     * @return array
     */
    public function validate()
    {
        \$errors = array();

{validators}
        
        return \$errors;
    }

}

STR;
        $tpl = \Tk\CurlyTemplate::create($classTpl);
        return $tpl;
    }


    /**
     * @param array $params any overrides for the curly template
     * @return string
     * @throws \Exception
     */
    public function makeMapper($params = array())
    {
        $tpl = $this->createMapperTemplate();
        $data = $this->getDefaultData();
        $classData = $this->processMapper();
        $data = array_merge($data, $classData, $params);
        return $tpl->parse($data);
    }

    /**
     * @return array
     */
    protected function processMapper()
    {
        $data = array(
            'column-maps' => '',
            'form-maps' => '',
            'filter-queries' => '',
            'set-table' => ''
        );
        foreach ($this->tableInfo as $col) {
            $mp = ModelProperty::create($col);
            if ($mp->getName() == 'del') continue;
            $data['column-maps'] .= $mp->getColumnMap() . "\n";
            $data['form-maps'] .= $mp->getFormMap() . "\n";
            if ($mp->getType() == ModelProperty::TYPE_DATE || $mp->get('Type') == 'text') continue;
            $data['filter-queries'] .= $mp->getFilterQuery() . "\n";
            if ($this->table != $this->tableFromClass()) {
                $data['set-table'] = "\n" . sprintf("            \$this->setTable('%s');", $this->table) . "\n";
            }
        }
        return $data;
    }


    /**
     * @return \Tk\CurlyTemplate
     */
    protected function createMapperTemplate()
    {
        $classTpl = <<<STR
<?php
namespace {namespace};

use Tk\Db\Tool;
use Tk\Db\Map\ArrayObject;
use Tk\DataMap\Db;
use Tk\DataMap\Form;
use Bs\Db\Mapper;
use Tk\Db\Filter;

/**
 * @author {author-name}
 * @created {date}
 * @link {author-www}
 * @license Copyright {year} {author-biz}
 */
class {classname}Map extends Mapper
{

    /**
     * @return \Tk\DataMap\DataMap
     */
    public function getDbMap()
    {
        if (!\$this->dbMap) { {set-table}
            \$this->dbMap = new \Tk\DataMap\DataMap();
{column-maps}
        }
        return \$this->dbMap;
    }

    /**
     * @return \Tk\DataMap\DataMap
     */
    public function getFormMap()
    {
        if (!\$this->formMap) {
            \$this->formMap = new \Tk\DataMap\DataMap();
{form-maps}
        }
        return \$this->formMap;
    }

    /**
     * @param array|Filter \$filter
     * @param Tool \$tool
     * @return ArrayObject|{classname}[]
     * @throws \Exception
     */
    public function findFiltered(\$filter, \$tool = null)
    {
        return \$this->selectFromFilter(\$this->makeQuery(\Tk\Db\Filter::create(\$filter)), \$tool);
    }

    /**
     * @param Filter \$filter
     * @return Filter
     */
    public function makeQuery(Filter \$filter)
    {
        \$filter->appendFrom('%s a', \$this->quoteParameter(\$this->getTable()));

        if (!empty(\$filter['keywords'])) {
            \$kw = '%' . \$this->escapeString(\$filter['keywords']) . '%';
            \$w = '';
            //\$w .= sprintf('a.name LIKE %s OR ', \$this->quote(\$kw));
            if (is_numeric(\$filter['keywords'])) {
                \$id = (int)\$filter['keywords'];
                \$w .= sprintf('a.id = %d OR ', \$id);
            }
            if (\$w) \$filter->appendWhere('(%s) AND ', substr(\$w, 0, -3));
        }

{filter-queries}

        if (!empty(\$filter['exclude'])) {
            \$w = \$this->makeMultiQuery(\$filter['exclude'], 'a.id', 'AND', '!=');
            if (\$w) \$filter->appendWhere('(%s) AND ', \$w);
        }

        return \$filter;
    }

}
STR;
        $tpl = \Tk\CurlyTemplate::create($classTpl);
        return $tpl;
    }


    /**
     * @param array $params any overrides for the curly template
     * @return string
     * @throws \Exception
     */
    public function makeTable($params = array())
    {
        $tpl = $this->createTableTemplate();
        $data = $this->getDefaultData();
        $classData = $this->processTable();
        $data = array_merge($data, $classData, $params);
        return $tpl->parse($data);
    }

    /**
     * @return array
     */
    protected function processTable()
    {
        $data = array(
            'cell-list' => '',
            'property-name' => lcfirst($this->className),
            'table-id' => str_replace('_', '-', $this->getTable())
        );
        foreach ($this->tableInfo as $col) {
            $mp = ModelProperty::create($col);
            if ($mp->getName() == 'del') continue;
            if ($mp->get('Type') != 'text')
                $data['cell-list'] .= $mp->getTableCell($this->getClassName(), $this->getNamespace()) . "\n";
        }
        return $data;
    }

    /**
     * @return \Tk\CurlyTemplate
     */
    protected function createTableTemplate()
    {
        $classTpl = <<<STR
<?php
namespace App\Table;

use Tk\Form\Field;
use Tk\Table\Cell;

/**
 * Example:
 * <code>
 *   \$table = new {classname}::create();
 *   \$table->init();
 *   \$list = ObjectMap::getObjectListing();
 *   \$table->setList(\$list);
 *   \$tableTemplate = \$table->show();
 *   \$template->appendTemplate(\$tableTemplate);
 * </code>
 * 
 * @author {author-name}
 * @created {date}
 * @link {author-www}
 * @license Copyright {year} {author-biz}
 */
class {classname} extends \Bs\TableIface
{
    
    /**
     * @return \$this
     * @throws \Exception
     */
    public function init()
    {
    
{cell-list}
        // Filters
        \$this->appendFilter(new Field\Input('keywords'))->setAttr('placeholder', 'Search');

        // Actions
        //\$this->appendAction(\Tk\Table\Action\Link::create('New {classname}', 'fa fa-plus', \Bs\Uri::createHomeUrl('/{property-name}Edit.html')));
        //\$this->appendAction(\Tk\Table\Action\ColumnSelect::create()->setUnselected(array('modified', 'created')));
        \$this->appendAction(\Tk\Table\Action\Delete::create());
        \$this->appendAction(\Tk\Table\Action\Csv::create());

        // load table
        //\$this->setList(\$this->findList());
        
        return \$this;
    }

    /**
     * @param array \$filter
     * @param null|\Tk\Db\Tool \$tool
     * @return \Tk\Db\Map\ArrayObject|\{namespace}\{classname}[]
     * @throws \Exception
     */
    public function findList(\$filter = array(), \$tool = null)
    {
        if (!\$tool) \$tool = \$this->getTool();
        \$filter = array_merge(\$this->getFilterValues(), \$filter);
        \$list = \{namespace}\{classname}Map::create()->findFiltered(\$filter, \$tool);
        return \$list;
    }

}
STR;
        $tpl = \Tk\CurlyTemplate::create($classTpl);
        return $tpl;
    }

    /**
     * @param array $params any overrides for the curly template
     * @return string
     * @throws \Exception
     */
    public function makeForm($params = array())
    {
        $tpl = $this->createFormIfaceTemplate();
        if (!empty($params['modelForm']))
            $tpl = $this->createModelFormTemplate();
        $data = $this->getDefaultData();
        $classData = $this->processForm(!empty($params['modelForm']));
        $data = array_merge($data, $classData, $params);
        return $tpl->parse($data);
    }

    /**
     * @param bool $isModelForm
     * @return array
     */
    protected function processForm($isModelForm = false)
    {
        $data = array(
            'property-name' => lcfirst($this->className),
            'form-id' => str_replace('_', '-', $this->getTable()),
            'field-list' => ''
        );
        foreach ($this->tableInfo as $col) {
            $mp = ModelProperty::create($col);
            if ($mp->getName() == 'del' || $mp->getName() == 'modified' || $mp->getName() == 'created' || $mp->getName() == 'id') continue;
            $data['field-list'] .= $mp->getFormField($this->getClassName(), $this->getNamespace(), $isModelForm) . "\n";
        }
        return $data;
    }

    /**
     * @return \Tk\CurlyTemplate
     */
    protected function createFormIfaceTemplate()
    {
        $classTpl = <<<STR
<?php
namespace App\Form;

use Tk\Form\Field;
use Tk\Form\Event;
use Tk\Form;

/**
 * Example:
 * <code>
 *   \$form = new {classname}::create();
 *   \$form->setModel(\$obj);
 *   \$formTemplate = \$form->getRenderer()->show();
 *   \$template->appendTemplate('form', \$formTemplate);
 * </code>
 * 
 * @author {author-name}
 * @created {date}
 * @link {author-www}
 * @license Copyright {year} {author-biz}
 */
class {classname} extends \Bs\FormIface
{

    /**
     * @throws \Exception
     */
    public function init()
    {
        
{field-list}
        \$this->appendField(new Event\Submit('update', array(\$this, 'doSubmit')));
        \$this->appendField(new Event\Submit('save', array(\$this, 'doSubmit')));
        \$this->appendField(new Event\Link('cancel', \$this->getBackUrl()));

    }

    /**
     * @param \Tk\Request \$request
     * @throws \Exception
     */
    public function execute(\$request = null)
    {
        \$this->load(\{namespace}\{classname}Map::create()->unmapForm(\$this->get{classname}()));
        parent::execute(\$request);
    }

    /**
     * @param Form \$form
     * @param Event\Iface \$event
     * @throws \Exception
     */
    public function doSubmit(\$form, \$event)
    {
        // Load the object with form data
        \{namespace}\{classname}Map::create()->mapForm(\$form->getValues(), \$this->get{classname}());

        // Do Custom Validations

        \$form->addFieldErrors(\$this->get{classname}()->validate());
        if (\$form->hasErrors()) {
            return;
        }
        
        \$isNew = (bool)\$this->get{classname}()->getId();
        \$this->get{classname}()->save();

        // Do Custom data saving

        \Tk\Alert::addSuccess('Record saved!');
        \$event->setRedirect(\$this->getBackUrl());
        if (\$form->getTriggeredEvent()->getName() == 'save') {
            \$event->setRedirect(\Tk\Uri::create()->set('{property-name}Id', \$this->get{classname}()->getId()));
        }
    }

    /**
     * @return \Tk\Db\ModelInterface|\{namespace}\{classname}
     */
    public function get{classname}()
    {
        return \$this->getModel();
    }

    /**
     * @param \{namespace}\{classname} \${property-name}
     * @return \$this
     */
    public function set{classname}(\${property-name})
    {
        return \$this->setModel(\${property-name});
    }
    
}
STR;
        $tpl = \Tk\CurlyTemplate::create($classTpl);
        return $tpl;
    }

    /**
     * @return \Tk\CurlyTemplate
     */
    protected function createModelFormTemplate()
    {
        $classTpl = <<<STR
<?php
namespace App\Form;

use Tk\Form\Field;
use Tk\Form\Event;
use Tk\Form;

/**
 * Example:
 * <code>
 *   \$form = new {classname}::create();
 *   \$form->setModel(\$obj);
 *   \$formTemplate = \$form->getRenderer()->show();
 *   \$template->appendTemplate('form', \$formTemplate);
 * </code>
 * 
 * @author {author-name}
 * @created {date}
 * @link {author-www}
 * @license Copyright {year} {author-biz}
 * @note Use this if you want to pass the form in rather than inherit the form
 */
class {classname} extends \Bs\ModelForm
{

    /**
     * @throws \Exception
     */
    public function init()
    {
        
{field-list}
        \$this->getForm()->appendField(new Event\Submit('update', array(\$this, 'doSubmit')));
        \$this->getForm()->appendField(new Event\Submit('save', array(\$this, 'doSubmit')));
        \$this->getForm()->appendField(new Event\Link('cancel', \$this->getBackUrl()));

    }

    /**
     * @param \Tk\Request \$request
     * @throws \Exception
     */
    public function execute(\$request = null)
    {
        \$this->getForm()->load(\{namespace}\{classname}Map::create()->unmapForm(\$this->get{classname}()));
        parent::execute(\$request);
    }

    /**
     * @param Form \$form
     * @param Event\Iface \$event
     * @throws \Exception
     */
    public function doSubmit(\$form, \$event)
    {
        // Load the object with form data
        \{namespace}\{classname}Map::create()->mapForm(\$form->getValues(), \$this->get{classname}());

        // Do Custom Validations

        \$form->addFieldErrors(\$this->get{classname}()->validate());
        if (\$form->hasErrors()) {
            return;
        }
        
        \$isNew = (bool)\$this->get{classname}()->getId();
        \$this->get{classname}()->save();

        // Do Custom data saving

        \Tk\Alert::addSuccess('Record saved!');
        \$event->setRedirect(\$this->getBackUrl());
        if (\$form->getTriggeredEvent()->getName() == 'save') {
            \$event->setRedirect(\Tk\Uri::create()->set('{property-name}Id', \$this->get{classname}()->getId()));
        }
    }

    /**
     * @return \Tk\Db\ModelInterface|\{namespace}\{classname}
     */
    public function get{classname}()
    {
        return \$this->getModel();
    }

    /**
     * @param \{namespace}\{classname} \${property-name}
     * @return \$this
     */
    public function set{classname}(\${property-name})
    {
        return \$this->setModel(\${property-name});
    }
    
}
STR;
        $tpl = \Tk\CurlyTemplate::create($classTpl);
        return $tpl;
    }


}