<?php
namespace Uni\Ui;


/**
 * @author Tropotek <info@tropotek.com>
 * @created: 21/08/18
 * @link http://www.tropotek.com/
 * @license Copyright 2018 Tropotek
 */
class MenuManager
{

    /**
     * @var MenuManager
     */
    public static $instance = null;

    /**
     * @var array|Menu[]
     */
    protected $list = array();



    /**
     * @return static
     */
    static protected function create()
    {
        $obj = new static();
        return $obj;
    }

    /**
     * @return MenuManager
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = self::create();
        }
        return self::$instance;
    }

    /**
     * @param string $name
     * @param string $roleType
     * @return Menu
     */
    public function createMenu($name, $roleType = 'public')
    {
        $menu = Menu::create($name);
        $menu->setTemplateVar($name);
        $menu->setRoleType($roleType);
        return $menu;
    }


    /**
     * If a menu does not exist with the given name then one is created with a public role type
     *
     * @param string $name
     * @param string $roleType
     * @return Menu
     */
    public function getMenu($name, $roleType = 'public')
    {
        $key = $roleType.'-'.$name;
        if (empty($this->list[$key])) {
            $this->list[$key] = $this->createMenu($name, $roleType);
        }
        return $this->list[$key];
    }

    /**
     * @return array|Menu[]
     */
    public function getMenuList()
    {
        return $this->list;
    }




}