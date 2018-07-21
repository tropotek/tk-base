<?php
namespace Bs\Util;



/**
 * @author Tropotek <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2017 Tropotek
 */
class ModelProperty extends \Tk\Collection
{

    const TYPE_STRING = 'string';
    const TYPE_INT = 'int';
    const TYPE_FLOAT = 'float';
    const TYPE_DATE = '\DateTime';
    const TYPE_BOOL = 'bool';



    /**
     * The new class property name
     * @var string
     */
    protected $name = '';

    /**
     * The new php property type
     * @var string
     */
    protected $type = '';



    /**
     * @param array $data
     */
    public function __construct($data = array())
    {
        parent::__construct($data);
        $this->getName();
        $this->getType();
    }

    /**
     * @param array $data
     * @return ModelProperty
     */
    public static function create($data)
    {
        $obj = new static($data);
        return $obj;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        return isset($this->data[$key]);
    }

    /**
     * @return bool
     */
    public function isPrimaryKey()
    {
        return (strtoupper($this->get('Key')) == 'PRI');
    }

    /**
     * @return string
     */
    public function getName()
    {
        if (!$this->name) {
            $this->name = preg_replace_callback('/_([a-z])/i', function ($matches) {
                return strtoupper($matches[1]);
            }, $this->get('Field'));
        }
        return $this->name;
    }

    /**
     * @return string
     */
    public function getType()
    {
        if (!$this->type) {
            $this->type = self::TYPE_STRING;
            $dtype = 'text';
            if (!empty($this->get('Type')))
                $dtype = strtolower($this->get('Type'));

            if (preg_match('/^(tinyint\(1\)|bool|boolean)/', $dtype)) {
                $this->type = self::TYPE_BOOL;
            } else if (preg_match('/^(int|bigint|tinyint|mediumint|integer|bit)/', $dtype)) {
                $this->type = self::TYPE_INT;
            } else if (preg_match('/^(float|decimal|dec|double)/', $dtype)) {
                $this->type = self::TYPE_FLOAT;
            } else if (preg_match('/^(datetime|timestamp|date|time|year)/', $dtype)) {
                $this->type = self::TYPE_DATE;
            }
        }
        return $this->type;
    }

    /**
     * @return bool|float|int|string
     */
    public function getDefaultValue()
    {
        $def = $this->get('Default');
        switch ($this->getType()) {
            case self::TYPE_BOOL:
                $def = boolval($def) ? 'true' : 'false';
                break;
            case self::TYPE_INT:
                $def = intval($def);
                break;
            case self::TYPE_FLOAT:
                $def = floatval($def);
                break;
            case self::TYPE_STRING:
                $def = $this->quote($def);
                break;
            default:
                $def = 'null';
        }
        return $def;
    }

    /**
     * @return string
     */
    public function getDefinition()
    {
        $tpl = <<<TPL
    /**
     * @var %s
     */
    public %s = %s;
TPL;
        return sprintf($tpl, $this->getType(), $this->getName(), $this->getDefaultValue());
    }

    /**
     * @return string
     */
    public function getInitaliser()
    {
        $tpl = <<<TPL
        \$this->%s = %s;
TPL;
        $val = $this->getDefaultValue();
        if (substr($this->getType(), 0 ,1) == '\\') {
            $val = sprintf('new %s()', $this->getType());
        }
        return sprintf($tpl, $this->getName(), $val);
    }

    /**
     * @param string $errorParam
     * @return string
     */
    public function getValidation($errorParam = 'errors')
    {
        $tpl = <<<TPL
        if (!\$this->%s) {
            \$%s['%s'] = 'Invalid value: %s';
        }
TPL;
        return sprintf($tpl, $this->getName(), $errorParam, $this->getName(), $this->getName());
    }



    public function getColumnMap()
    {
        $tpl = <<<TPL
            \$this->dbMap->addPropertyMap(new %s(%s%s)%s)
TPL;

        $mapClass = 'Db\Text';
        switch ($this->getType()) {
            case self::TYPE_INT:
                $mapClass = 'Db\Integer';
                break;
            case self::TYPE_FLOAT:
                $mapClass = 'Db\Decimal';
                break;
            case self::TYPE_BOOL:
                $mapClass = 'Db\Boolean';
                break;
            case self::TYPE_DATE:
                $mapClass = 'Db\Date';
                break;
        }

        $propertyName = $this->quote($this->getName());
        $columnName = '';
        if ($this->getName() != $this->get('Field'))
            $columnName = ', '.$this->quote($this->get('Field'));
        $tag = '';
        if ($this->isPrimaryKey())
            $tag = ', ' . $this->quote('key');

        return sprintf($tpl, $mapClass, $propertyName, $columnName, $tag);
    }


    public function getFormMap()
    {
        $tpl = <<<TPL
            \$this->formMap->addPropertyMap(new %s(%s)%s);
TPL;

        $mapClass = 'Form\Text';
        switch ($this->getType()) {
            case self::TYPE_INT:
                $mapClass = 'Form\Integer';
                break;
            case self::TYPE_FLOAT:
                $mapClass = 'Form\Decimal';
                break;
            case self::TYPE_BOOL:
                $mapClass = 'Form\Boolean';
                break;
            case self::TYPE_DATE:
                $mapClass = 'Form\Date';
                break;
        }

        $propertyName = $this->quote($this->getName());
        $tag = '';
        if ($this->isPrimaryKey())
            $tag = ', ' . $this->quote('key');

        return sprintf($tpl, $mapClass, $propertyName, $tag);
    }



    public function getFilterQuery()
    {
        $tpl = <<<TPL
        if (!empty(\$filter['%s'])) {
            \$where .= sprintf('a.%s = %%s AND ', %s);
        }
TPL;

        $filterVal = sprintf("\$this->quote(\$filter['%s'])", $this->getName());
        switch ($this->getType()) {
            case self::TYPE_INT:
                $filterVal = sprintf("(int)\$filter['%s']", $this->getName());
                break;
            case self::TYPE_FLOAT:
                $filterVal = sprintf("(float)\$filter['%s']", $this->getName());
                break;
            case self::TYPE_BOOL:
                $filterVal = sprintf("(int)\$filter['%s']", $this->getName());
                break;
        }

        return sprintf($tpl, $this->getName(), $this->get('Field'), $filterVal);

    }



    /**
     * @param $str
     * @param string $q
     * @return string
     */
    protected function quote($str, $q = "'")
    {
        return $q.$str.$q;
    }

}