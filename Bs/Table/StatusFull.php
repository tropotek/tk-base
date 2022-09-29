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
     * @var null|callable
     */
    protected $onCourseShowOption = null;

    /**
     * @var null|callable
     */
    protected $onSubjectShowOption = null;


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

        // status name
        $list = \Bs\Db\StatusMap::create()->findCourses(['institutionId' => $this->getConfig()->getInstitutionId()]);
        $this->appendFilter(\Tk\Form\Field\CheckboxSelect::createSelect('courseId', $list)); // ->addOnShowOption($this->onCourseShowOption);
        // status name
        $list = \Bs\Db\StatusMap::create()->findSubjects(['institutionId' => $this->getConfig()->getInstitutionId()]);
        $this->appendFilter(\Tk\Form\Field\CheckboxSelect::createSelect('strictSubjectId', $list)); //->addOnShowOption($this->onSubjectShowOption));
        // status name
        $list = \Bs\Db\StatusMap::create()->findNames(['institutionId' => $this->getConfig()->getInstitutionId()]);
        $this->appendFilter(\Tk\Form\Field\CheckboxSelect::createSelect('name', $list));
        // event
        $list = \Bs\Db\StatusMap::create()->findEvents(['institutionId' => $this->getConfig()->getInstitutionId()]);
        $this->appendFilter(\Tk\Form\Field\CheckboxSelect::createSelect('event', $list));
        // fkey
        $list = \Bs\Db\StatusMap::create()->findFkeys(['institutionId' => $this->getConfig()->getInstitutionId()]);
        $this->appendFilter(\Tk\Form\Field\CheckboxSelect::createSelect('fkey', $list));

        $this->appendFilter(new Field\DateRange('date'));

        //$this->resetSession();

        // Actions
        $this->appendAction(ColumnSelect::create()->setUnselected(['message', 'event', 'fid'])->setSelected([]));
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

    /**
     * @return callable|null
     */
    public function getOnCourseShowOption(): ?callable
    {
        return $this->onCourseShowOption;
    }

    /**
     * @param callable|null $onCourseShowOption
     * @return StatusFull
     */
    public function setOnCourseShowOption(?callable $onCourseShowOption): StatusFull
    {
        $this->onCourseShowOption = $onCourseShowOption;
        return $this;
    }

    /**
     * @return callable|null
     */
    public function getOnSubjectShowOption(): ?callable
    {
        return $this->onSubjectShowOption;
    }

    /**
     * @param callable|null $onSubjectShowOption
     * @return StatusFull
     */
    public function setOnSubjectShowOption(?callable $onSubjectShowOption): StatusFull
    {
        $this->onSubjectShowOption = $onSubjectShowOption;
        return $this;
    }

}