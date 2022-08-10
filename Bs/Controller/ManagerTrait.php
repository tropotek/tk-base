<?php
namespace Bs\Controller;

/**
 * @author Tropotek <http://www.tropotek.com/>
 * @link http://www.tropotek.com/
 * @license Copyright 2017 Tropotek
 */
trait ManagerTrait
{
    /**
     * @var \Tk\Table|\Bs\TableIface
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
     * @return \Tk\Table|\Bs\TableIface
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @param \Tk\Table|\Bs\TableIface $table
     */
    public function setTable($table)
    {
        $this->table = $table;
    }

}