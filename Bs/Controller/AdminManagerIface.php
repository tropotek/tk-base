<?php
namespace Bs\Controller;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class AdminManagerIface extends AdminIface
{

    /**
     * @var \Tk\Table
     */
    protected $table = null;

    /**
     * @var \Tk\Table\Cell\Actions
     */
    protected $actionsCell = null;


    public function getActionsCell()
    {
        if (!$this->actionsCell) {
            $this->actionsCell = new \Tk\Table\Cell\Actions();
        }
        return $this->actionsCell;
    }

    /**
     * @return \Tk\Table
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @param \Tk\Table $table
     */
    public function setTable($table)
    {
        $this->table = $table;
    }

}