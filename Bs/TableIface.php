<?php
namespace Bs;

/**
 * @author Tropotek <info@tropotek.com>
 * @created: 22/07/18
 * @link http://www.tropotek.com/
 * @license Copyright 2018 Tropotek
 */
class TableIface extends \Tk\Table
{

    /**
     * @param string $tableId
     */
    public function __construct($tableId = '')
    {
        if (!$tableId)
            $tableId = strtolower(preg_replace('/[A-Z]/', '-$0', \Tk\ObjectUtil::basename(get_class($this))));
        parent::__construct($tableId);
    }

    /**
     * @param $id
     * @return static|TableIface|\Tk\Table
     */
    public static function create($id = '')
    {
        $obj = parent::create($id);
        $obj->setRenderer(\Bs\Config::getInstance()->createTableRenderer($obj));
        return $obj;
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return Config::getInstance();
    }

    /**
     * @return \Tk\Uri
     */
    public function getBackUrl()
    {
        return $this->getConfig()->getBackUrl();
    }

    /**
     * @return Db\User
     */
    public function getUser()
    {
        return $this->getConfig()->getUser();
    }

}