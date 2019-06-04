<?php
namespace Bs\Table;


use Exception;
use Tk\Alert;
use Tk\Db\Map\ArrayObject;
use Tk\Db\Tool;
use Tk\Form\Field\Input;
use Tk\Table\Action\Csv;
use Tk\Table\Action\Delete;
use Tk\Table\Cell\Boolean;
use Tk\Table\Cell\Checkbox;
use Tk\Table\Cell\Date;
use Tk\Table\Cell\Text;
use Bs\TableIface;

/**
 * @author Mick Mifsud
 * @created 2018-07-24
 * @link http://tropotek.com.au/
 * @license Copyright 2018 Tropotek
 */
class Role extends TableIface
{


    /**
     * @return $this
     * @throws Exception
     */
    public function init()
    {
        $this->appendCell(new Checkbox('id'));
        $this->appendCell(new Text('name'))->addCss('key')->setUrl($this->getEditUrl());
        $this->appendCell(Text::create('description')->setCharacterLimit(100));
        $this->appendCell(new Boolean('active'));
        $this->appendCell(new Boolean('static'));
        $this->appendCell(Date::create('created')->setFormat(\Tk\Date::FORMAT_ISO_DATE));


        // Filters
        $this->appendFilter(new Input('keywords'))->setLabel('')->setAttr('placeholder', 'Search');

        // Actions
        $this->appendAction(Delete::create()->setOnDelete(function (Delete $action, $obj) {
            /** @var \Bs\Db\Role $obj */
            if ($obj->isStatic()) {
                Alert::addWarning('Cannot delete system static roles.');
                return false;
            }
        }));
        $this->appendAction(Csv::create());

        $filter = $this->getFilterValues();
        if (empty($filter['type'])) {
            $filter['type'] = array(
                \Bs\Db\Role::TYPE_ADMIN
                //,\Bs\Db\Role::TYPE_USER
            );
        }
        //$filter['institutionId'] = $this->getConfig()->getInstitutionId();


        return $this;
    }

    /**
     * @param array $filter
     * @param null|Tool $tool
     * @return ArrayObject|\Bs\Db\Role[]
     * @throws Exception
     */
    public function findList($filter = array(), $tool = null)
    {
        if (!$tool) $tool = $this->getTool();
        $filter = array_merge($this->getFilterValues(), $filter);
        $list = $this->getConfig()->getRoleMapper()->findFiltered($filter, $tool);
        return $list;
    }

}