<?php
namespace Bs\Ui;


/**
 * @author Tropotek <info@tropotek.com>
 * @created: 21/08/18
 * @link http://www.tropotek.com/
 * @license Copyright 2018 Tropotek
 * @todo: remove this over time it is not really needed
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
     * @return Menu|\Tk\Ui\Menu\Menu
     */
    public function createMenu($name)
    {
        $menu = Menu::create($name);
        return $menu;
    }

    /**
     * If a menu does not exist with the given name then one is created with a public role type
     *
     * @param string $name
     * @return Menu|null
     */
    public function getMenu($name)
    {
        if (!$name) return null;
        if (empty($this->list[$name])) {
            $this->list[$name] = $this->createMenu($name);
        }
        return $this->list[$name];
    }

    /**
     * @return array|Menu[]
     */
    public function getMenuList()
    {
        return $this->list;
    }

}