<?php
namespace Bs\Table;

use App\Form\Field\DateRange;
use Bs\Db\Permission;
use Bs\Db\Status as StatusAlias;
use Tk\Db\Map\ArrayObject;
use Tk\Db\Tool;
use Tk\Form\Field\Input;
use Tk\Table\Action\ColumnSelect;
use Tk\Table\Action\Csv;
use Tk\Table\Cell;
use Tk\Table\Cell\Text;
use Bs\Db\StatusMap;
use Tk\Form\Field;

/**
 * Example:
 * <code>
 *   $table = new StatusFull::create();
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
class StatusFull extends \Bs\TableIface
{


    /**
     * @return $this
     * @throws \Exception
     */
    public function init()
    {
        $this->addCss('tk-status-table');

        //$this->appendCell(new Cell\Checkbox('id'));
        $this->appendCell(new Text('userId'))->addOnPropertyValue(function ($cell, $obj, $value) {
            /** @var $obj StatusAlias */
            $value = '';
            if ($obj->getUser())
                $value = $obj->getUser()->getName();
            return $value;
        });
        $this->appendCell(new Text('name'))->setLabel('Status')->setUrl($this->getEditUrl());
        $this->appendCell(new Text('courseId'));
        $this->appendCell(new Text('subjectId'));
        $this->appendCell(new Text('event'));
        $this->appendCell(new Text('fkey'));
        $this->appendCell(new Text('fid'));

        $this->appendCell(new Text('message'))->addCss('key wrap-normal')
            ->addOnCellHtml(function ($cell, $obj, $html) {
                /** @var $cell Text */
                /** @var $obj StatusAlias */
                $cell->setAttr('title', 'Message');
                return '<small>' . nl2br(\Tk\Str::stripEntities($html)) . '</small>' ;
            });
        $this->appendCell(new Cell\Date('created'));

        // Filters
        $this->appendFilter(new Input('keywords'))->setAttr('placeholder', 'Search');

        // courseId     // tkuni only
        // subjectId    // tk uni only

        // event
        $list = \Bs\Db\StatusMap::create()->findEvents([]);
        $this->appendFilter(new \Tk\Form\Field\CheckboxSelect('event', $list));
        // status name
        $list = \Bs\Db\StatusMap::create()->findNames([]);
        $this->appendFilter(new \Tk\Form\Field\CheckboxSelect('name', $list));
        // fkey
        $list = \Bs\Db\StatusMap::create()->findFkeys([]);
        $this->appendFilter(new \Tk\Form\Field\CheckboxSelect('fkey', $list));

        $this->appendFilter(new Field\DateRange('date'));

        //$this->resetSession();

        // Actions
        $this->appendAction(ColumnSelect::create()->setUnselected(['message', 'fid'])->setSelected([]));
        $this->appendAction(\Tk\Table\Action\Csv::create());

        return $this;
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
        $list = StatusMap::create()->findFiltered($filter, $tool);
        return $list;
    }

}