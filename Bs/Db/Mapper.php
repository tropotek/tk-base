<?php
namespace Bs\Db;

use Tk\Db\Map\Model;
use Bs\Event\DbEvent;
use Bs\DbEvents;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2016 Michael Mifsud
 */
abstract class Mapper extends \Tk\Db\Mapper
{

    /***
     * @var \Tk\Event\Dispatcher
     */
    protected $dispatcher = null;


    /**
     * @param \Tk\Db\Pdo|null $db
     * @throws \Exception
     */
    public function __construct($db = null)
    {
        $this->dispatcher = $this->getConfig()->getEventDispatcher();
        parent::__construct($db);
        $this->setMarkDeleted('del');           // Default to have a del field (This will only mark the record deleted)
    }

    /**
     * Insert
     *
     * @param Model $obj
     * @return int Returns the new insert id
     */
    public function insert($obj)
    {
        $stop = false;
        if ($this->getDispatcher()) {
            $e = new DbEvent($obj, $this);
            $this->getDispatcher()->dispatch(DbEvents::MODEL_INSERT, $e);
            $stop = $e->isQueryStopped();
        }
        if (!$stop) {
            $r = parent::insert($obj);
            return $r;
        }
        return 0;
    }

    /**
     *
     * @param Model $obj
     * @return int
     */
    public function update($obj)
    {
        $stop = false;
        if ($this->getDispatcher()) {
            $e = new DbEvent($obj, $this);
            $this->getDispatcher()->dispatch(DbEvents::MODEL_UPDATE, $e);
            $stop = $e->isQueryStopped();
        }
        if (!$stop) {
            $r = parent::update($obj);
            return $r;
        }
        return 0;
    }

    /**
     * Save the object, let the code decide weather to insert or update the db.
     *
     * @param Model $obj
     * @throws \Exception
     */
    public function save($obj)
    {
        $stop = false;
        if ($this->getDispatcher()) {
            $e = new DbEvent($obj, $this);
            $this->getDispatcher()->dispatch(DbEvents::MODEL_SAVE, $e);
            $stop = $e->isQueryStopped();
        }
        if (!$stop) {
            parent::save($obj);
        }
    }

    /**
     * Delete object
     *
     * @param Model $obj
     * @return int
     */
    public function delete($obj)
    {
        $stop = false;
        if ($this->getDispatcher()) {
            $e = new DbEvent($obj, $this);
            $this->getDispatcher()->dispatch(DbEvents::MODEL_DELETE, $e);
            $stop = $e->isQueryStopped();
        }
        if (!$stop) {
            $r = parent::delete($obj);
            return $r;
        }
        return 0;
    }

    /**
     * @return \Tk\Event\Dispatcher
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * @param \Tk\Event\Dispatcher $dispatcher
     * @return $this
     */
    public function setDispatcher($dispatcher)
    {
        $this->dispatcher = $dispatcher;
        return $this;
    }

    /**
     * @return \Bs\Config
     */
    public function getConfig()
    {
        return \Bs\Config::getInstance();
    }

}