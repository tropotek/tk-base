<?php
namespace Bs\Controller;

/**
 * @author Tropotek <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2017 Tropotek
 */
trait ManagerTrait
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