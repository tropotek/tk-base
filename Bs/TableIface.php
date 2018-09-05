<?php
namespace Bs;

use Dom\Renderer\Renderer;
use Dom\Template;

/**
 * @author Tropotek <info@tropotek.com>
 * @created: 22/07/18
 * @link http://www.tropotek.com/
 * @license Copyright 2018 Tropotek
 */
class TableIface extends \Tk\Table implements \Dom\Renderer\DisplayInterface
{



    /**
     * @param string $tableId
     */
    public function __construct($tableId = '')
    {
        if (!$tableId)
            $tableId = trim(strtolower(preg_replace('/[A-Z]/', '_$0', \Tk\ObjectUtil::basename(get_class($this)))), '_');
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
        $obj->setDispatcher(\Bs\Config::getInstance()->getEventDispatcher());
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

    /**
     * Execute the renderer.
     * Return an object that your framework can interpret and display.
     *
     * @return null|Template|Renderer
     */
    public function show()
    {
        return $this->getRenderer()->show();
    }

    /**
     * Get the Template
     *
     * @return Template
     */
    public function getTemplate()
    {
        return $this->getRenderer()->getTemplate();
    }

    /**
     * Set the Template
     *
     * @param Template $template
     */
    public function setTemplate($template)
    {
        $this->getRenderer()->setTemplate($template);
    }
}