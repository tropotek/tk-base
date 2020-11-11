<?php
namespace Bs\Table;

use Bs\Db\Status as StatusAlias;
use Bs\Db\StatusMap;
use Bs\Db\Traits\StatusTrait;
use Tk\Db\Map\ArrayObject;
use Tk\Db\Tool;
use Tk\Form\Field\Select;
use Tk\Table\Cell\Date;
use Tk\Table\Cell\Iface;
use Tk\Table\Cell\Text;
use Tk\Table\Renderer\Dom\Div;

/**
 * Example:
 * <code>
 *   $table = new Status::create();
 *   $table->init();
 *   $list = ObjectMap::getObjectListing();
 *   $table->setList($list);
 *   $tableTemplate = $table->show();
 *   $template->appendTemplate($tableTemplate);
 * </code>
 * 
 * @author Mick Mifsud
 * @created 2019-05-23
 * @link http://tropotek.com.au/
 * @license Copyright 2019 Tropotek
 */
class StatusPending extends \Bs\TableIface
{

    /**
     * Supervisor constructor.
     * @param string $tableId
     */
    public function __construct($tableId = '')
    {
        parent::__construct($tableId);
        $this->setRenderer(Div::create($this));
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function init()
    {
        $this->addCss('status-table');

        $this->appendCell(new Text('id'))->setLabel('ID')->addOnCellHtml(function ($cell, $obj, $html) {
            /** @var Iface $cell */
            /** @var StatusAlias|StatusTrait $obj */
            if ($obj->getModel()) {
                $cell->setAttr('title', $obj->getLabel());
                return $obj->getPendingIcon();
            }
            return '';
        });

        $this->appendCell(new Text('name'))->addOnCellHtml(function ($cell, $obj, $html) {
            /** @var Iface $cell */
            /** @var StatusAlias|StatusTrait $obj */
            $cell->removeAttr('title');
            if ($obj->getModel()) {
                return $obj->getPendingHtml();
            }
            return '';
        });

        // Actions
        $this->appendCell($this->getActionCell());

        $this->appendCell(Date::createDate('created'))->addOnCellHtml(function ($cell, $obj, $html) {
            /** @var Iface $cell */
            /** @var StatusAlias $obj */
            $cell->removeAttr('title');
            return sprintf('<div class="status-created">%s</div>', $obj->getCreated(\Tk\Date::FORMAT_MED_DATE));
        });

        // Filters
        $list = $this->getSelectList();
        /** @var Select $select */
        $select = $this->appendFilter(new Select('fkey', $list));
        $select->prependOption('-- Type --', '')->setAttr('placeholder', 'Keywords');

        return $this;
    }

    protected function getSelectList()
    {
        $filter = array();
        return StatusMap::create()->findKeys($filter);
    }

    /**
     * @param array $filter
     * @param null|Tool $tool
     * @return ArrayObject|StatusAlias[]
     * @throws \Exception
     */
    public function findList($filter = array(), $tool = null)
    {
        if (!$tool) $tool = $this->getTool('created DESC');
        $filter = array_merge($this->getFilterValues(), $filter);
        $list = StatusMap::create()->findCurrentStatus($filter, $tool);
        return $list;
    }

}