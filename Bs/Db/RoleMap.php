<?php
namespace Bs\Db;

use Tk\Date;
use Tk\Db\Tool;
use Tk\Db\Map\ArrayObject;
use Tk\DataMap\Db;
use Tk\DataMap\Form;
use Uni\Db\Role;
use Uni\Db\SubjectMap;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class RoleMap extends Mapper
{
    /**
     * @return \Tk\DataMap\DataMap
     * @throws \Exception
     */
    public function getDbMap()
    {
        if (!$this->dbMap) {
            $this->setMarkDeleted('del');
            $this->setTable('user_role');
            $this->dbMap = new \Tk\DataMap\DataMap();
            $this->dbMap->addPropertyMap(new Db\Integer('id'), 'key');
            $this->dbMap->addPropertyMap(new Db\Text('name'));
            $this->dbMap->addPropertyMap(new Db\Text('type'));
            $this->dbMap->addPropertyMap(new Db\Text('description'));
            $this->dbMap->addPropertyMap(new Db\Boolean('active'));
            $this->dbMap->addPropertyMap(new Db\Boolean('static'));
            $this->dbMap->addPropertyMap(new Db\Date('modified'));
            $this->dbMap->addPropertyMap(new Db\Date('created'));
        }
        return $this->dbMap;
    }

    /**
     * @return \Tk\DataMap\DataMap
     */
    public function getFormMap()
    {
        if (!$this->formMap) {
            $this->formMap = new \Tk\DataMap\DataMap();
            $this->formMap->addPropertyMap(new Form\Integer('id'), 'key');
            $this->formMap->addPropertyMap(new Form\Text('name'));
            $this->formMap->addPropertyMap(new Form\Text('type'));
            $this->formMap->addPropertyMap(new Form\Text('description'));
            $this->formMap->addPropertyMap(new Form\Boolean('active'));
        }
        return $this->formMap;
    }

    /**
     * Get a list of all the available roleTypes from the role table
     */
    public function findAllTypes()
    {
        $arr = array();
        try {
            $sql = sprintf('SELECT DISTINCT type FROM %s', $this->quoteTable($this->getTable()));
            $stm = $this->getDb()->query($sql);
            $arr = $stm->fetchAll(\PDO::FETCH_ASSOC | \PDO::FETCH_COLUMN);
        } catch (\Exception $e) { \Tk\Log::warning($e->getMessage()); }
        return $arr;
    }


    /**
     * @param array|\Tk\Db\Filter $filter
     * @param Tool $tool
     * @return ArrayObject|Role[]
     * @throws \Exception
     */
    public function findFiltered($filter, $tool = null)
    {
        return $this->selectFromFilter($this->makeQuery(\Tk\Db\Filter::create($filter)), $tool);
    }

    /**
     * @param \Tk\Db\Filter $filter
     * @return \Tk\Db\Filter
     */
    public function makeQuery(\Tk\Db\Filter $filter)
    {
        $filter->appendFrom('%s a', $this->quoteParameter($this->getTable()));

        if (!empty($filter['keywords'])) {
            $kw = '%' . $this->getDb()->escapeString($filter['keywords']) . '%';
            $w = '';
            $w .= sprintf('a.name LIKE %s OR ', $this->getDb()->quote($kw));
            $w .= sprintf('a.type LIKE %s OR ', $this->quote($kw));
            if (is_numeric($filter['keywords'])) {
                $id = (int)$filter['keywords'];
                $w .= sprintf('a.id = %d OR ', $id);
            }
            if ($w) {
                $filter->appendWhere('(%s) AND ', substr($w, 0, -3));
            }
        }


        if (!empty($filter['name'])) {
            $filter->appendWhere('a.name = %s AND ', $this->getDb()->quote($filter['name']));
        }

        if (!empty($filter['username'])) {
            $filter->appendWhere('a.username = %s AND ', $this->getDb()->quote($filter['username']));
            if (!empty($filter['password'])) {
                $filter->appendWhere('a.password = %s AND ', $this->getDb()->quote($filter['password']));
            }
        }

        if (!empty($filter['active'])) {
            $filter->appendWhere('a.active = %s AND ', (int)$filter['active']);
        }

        if (!empty($filter['static'])) {
            $filter->appendWhere('a.static = %s AND ', (int)$filter['static']);
        }

        if (!empty($filter['type'])) {
            $w = $this->makeMultiQuery($filter['type'], 'a.type');
            if ($w) {
                $filter->appendWhere('(%s) AND ', $w);
            }
        }

        if (!empty($filter['exclude'])) {
            $w = $this->makeMultiQuery($filter['exclude'], 'a.id', 'AND', '!=');
            if ($w) {
                $filter->appendWhere('(%s) AND ', $w);
            }
        }

        return $filter;
    }




    // --------------------------------------------


    /**
     * Note: Be sure to check the active status of this role
     *       and return false if this is a non active role.
     *
     * @param int $roleId
     * @param string $name
     * @return bool
     * @throws \Exception
     */
    public function hasPermission($roleId, $name)
    {
        $stm = $this->getDb()->prepare('SELECT * FROM user_permission WHERE role_id = ? AND name = ?');
        $stm->execute(array($roleId, $name));
        return ($stm->rowCount() > 0);
    }

    /**
     * @param int $roleId
     * @param string $name
     * @throws \Exception
     */
    public function addPermission($roleId, $name)
    {
        if ($name && !$this->hasPermission($roleId, $name)) {
            $stm = $this->getDb()->prepare('INSERT INTO user_permission (role_id, name)  VALUES (?, ?)');
            $stm->execute(array($roleId, $name));
        }
    }

    /**
     * @param int $roleId
     * @param string $name
     * @throws \Exception
     */
    public function removePermission($roleId, $name = null)
    {
        if ($name !== null) {
            if ($this->hasPermission($roleId, $name)) {
                $stm = $this->getDb()->prepare('DELETE FROM user_permission WHERE role_id = ? AND name = ?');
                $stm->execute(array($roleId, $name));
            }
        } else {
            $stm = $this->getDb()->prepare('DELETE FROM user_permission WHERE role_id = ?');
            $stm->execute(array($roleId));
        }
    }

    /**
     * @param int $roleId
     * @return array
     * @throws \Exception
     */
    public function getPermissions($roleId)
    {
        $stm = $this->getDb()->prepare('SELECT * FROM user_permission a WHERE a.role_id = ?');
        $stm->execute(array($roleId));
        $arr = array();
        foreach ($stm as $row) {
            $arr[] = $row->name;
        }
        return $arr;
    }
}