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
     * @var \Tk\Table\Cell\Actions
     */
    protected $actionCell = null;

    /**
     * @var null|\Tk\Uri
     */
    protected $editUrl = null;

    /**
     * @param string $tableId
     */
    public function __construct($tableId = '')
    {
        parent::__construct($tableId);
    }

    /**
     * @param $id
     * @return static|TableIface|\Tk\Table
     */
    public static function create($id = '')
    {
        $obj = parent::create($id);
        if (!$obj->getRenderer())
            $obj->setRenderer(\Bs\Config::getInstance()->createTableRenderer($obj));
        if (!$obj->getDispatcher())
            $obj->setDispatcher(\Bs\Config::getInstance()->getEventDispatcher());
        return $obj;
    }


    /**
     * @return $this
     * @throws \Exception
     */
    public function init()
    {
        $this->initCells();
        $this->initFilters();
        $this->initActions();
        return $this;
    }

    /**
     * append cells to the table
     */
    protected function initCells() { }

    /**
     * Append any filters to the table
     */
    protected function initFilters() { }

    /**
     * Append any actions to the table
     */
    protected function initActions() { }


    /**
     * @return \Tk\Table\Cell\Actions
     */
    public function getActionCell()
    {
        if (!$this->actionCell) {
            $this->actionCell = new \Tk\Table\Cell\Actions();
        }
        return $this->actionCell;
    }

    /**
     * @return null|\Tk\Uri
     */
    public function getEditUrl()
    {
        return $this->editUrl;
    }

    /**
     * @param null|\Tk\Uri $editUrl
     * @return $this
     */
    public function setEditUrl($editUrl)
    {
        $this->editUrl = $editUrl;
        return $this;
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


    /**
     * @param array $filter
     * @param null|\Tk\Db\Tool $tool
     * @return \Tk\Db\Map\ArrayObject|array|null
     * @throws \Exception
     */
    public function findList($filter = array(), $tool = null)
    {
        return array();
    }

}