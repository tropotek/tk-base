<?php
namespace Bs\Db;

use Tk\Db\Tool;
use Tk\Db\Map\ArrayObject;
use Tk\DataMap\Db;
use Tk\DataMap\Form;

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
     * @param array $filter
     * @param Tool $tool
     * @return ArrayObject|Role[]
     * @throws \Exception
     */
    public function findFiltered($filter = array(), $tool = null)
    {
        $this->makeQuery($filter, $tool, $where, $from);
        if ($where) {
            $where = rtrim($where, 'AND ');
        }
        $res = $this->selectFrom($from, $where, $tool);
        return $res;
    }


    /**
     * @param array $filter
     * @param Tool $tool
     * @param string $where
     * @param string $from
     * @return $this
     */
    public function makeQuery($filter = array(), $tool = null, &$where = '', &$from = '')
    {
        $from .= sprintf('%s a ', $this->quoteParameter($this->getTable()));

        if (!empty($filter['keywords'])) {
            $kw = '%' . $this->escapeString($filter['keywords']) . '%';
            $w = '';
            $w .= sprintf('a.name LIKE %s OR ', $this->quote($kw));
            $w .= sprintf('a.type LIKE %s OR ', $this->quote($kw));
            if (is_numeric($filter['keywords'])) {
                $id = (int)$filter['keywords'];
                $w .= sprintf('a.id = %d OR ', $id);
            }
            if ($w) {
                $where .= '(' . substr($w, 0, -3) . ') AND ';
            }
        }

        if (!empty($filter['name'])) {
            $where .= sprintf('a.name = %s AND ', $this->getDb()->quote($filter['name']));
        }

        if (!empty($filter['username'])) {
            $where .= sprintf('a.username = %s AND ', $this->getDb()->quote($filter['username']));
            if (!empty($filter['password'])) {
                $where .= sprintf('a.password = %s AND ', $this->getDb()->quote($filter['password']));
            }
        }

        if (!empty($filter['active'])) {
            $where .= sprintf('a.active = %s AND ', (int)$filter['active']);
        }

        if (!empty($filter['static'])) {
            $where .= sprintf('a.static = %s AND ', (int)$filter['static']);
        }

        if (!empty($filter['type'])) {
            $w = $this->makeMultiQuery($filter['type'], 'a.type');
            if ($w) {
                $where .= '('. $w . ') AND ';
            }
        }

        if (!empty($filter['exclude'])) {
            $w = $this->makeMultiQuery($filter['exclude'], 'a.id', 'AND', '!=');
            if ($w) {
                $where .= '('. $w . ') AND ';
            }
        }
        return $this;
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