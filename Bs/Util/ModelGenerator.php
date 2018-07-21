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
    public function setClassName(string $className)
    {
        $this->className = $className;
        return $this;
    }

    /**
     * @param string $namespace
     * @return static
     */
    public function setNamespace(string $namespace)
    {
        $this->namespace = $namespace;
        return $this;
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
            if ($mp->get('Null') == 'NO' && $mp->getName() != 'id')
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
                $data['set-table'] = "\n" . sprintf("            $this->setTable('%s');", $this->table) . "\n";
            }
        }
        return $data;
    }

    /**
     * @return string
     */
    protected function tableFromClass()
    {
        return ltrim(strtolower(preg_replace('/[A-Z]/', '_$0', $this->className)), '_');
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
     *
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
     * @param array \$filter
     * @param Tool \$tool
     * @return ArrayObject|{classname}[]
     * @throws \Exception
     */
    public function findFiltered(\$filter = array(), \$tool = null)
    {
        \$this->makeQuery(\$filter, \$tool, \$where, \$from);
        \$res = \$this->selectFrom(\$from, \$where, \$tool);
        return \$res;
    }

    /**
     * @param array \$filter
     * @param Tool \$tool
     * @param string \$where
     * @param string \$from
     * @return ArrayObject|{classname}[]
     */
    public function makeQuery(\$filter = array(), \$tool = null, &\$where = '', &\$from = '')
    {
        \$from .= sprintf('%s a ', \$this->quoteParameter(\$this->getTable()));

        if (!empty(\$filter['keywords'])) {
            \$kw = '%' . \$this->escapeString(\$filter['keywords']) . '%';
            \$w = '';
            //\$w .= sprintf('a.name LIKE %s OR ', \$this->quote(\$kw));
            if (is_numeric(\$filter['keywords'])) {
                \$id = (int)\$filter['keywords'];
                \$w .= sprintf('a.id = %d OR ', \$id);
            }
            if (\$w) \$where .= '(' . substr(\$w, 0, -3) . ') AND ';

        }

{filter-queries}

        if (!empty(\$filter['ignore'])) {
            \$w = \$this->makeMultiQuery(\$filter['ignore'], 'a.id', 'AND', '!=');
            if (\$w) \$where .= '('. \$w . ') AND ';

        }

        if (\$where) {
            \$where = substr(\$where, 0, -4);
        }

        return \$where;
    }

}

STR;
        $tpl = \Tk\CurlyTemplate::create($classTpl);
        return $tpl;
    }


}