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
     * @param $id
     * @param array $params
     * @return static|TableIface|\Tk\Table
     */
    public static function create($id, $params = null)
    {
        $obj = parent::create($id, $params);
        $obj->setRenderer(\Bs\Config::getInstance()->createTableRenderer($obj));
        return $obj;
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return \Bs\Config::getInstance();
    }

    /**
     * @return \Tk\Uri
     * @throws \Exception
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