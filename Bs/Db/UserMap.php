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
class UserMap extends Mapper
{
    /**
     *
     * @return \Tk\DataMap\DataMap
     */
    public function getDbMap()
    {
        if (!$this->dbMap) {
            $this->setMarkDeleted('del');
            $this->dbMap = new \Tk\DataMap\DataMap();
            $this->dbMap->addPropertyMap(new Db\Integer('id'), 'key');
            $this->dbMap->addPropertyMap(new Db\Integer('roleId', 'role_id'));
            $this->dbMap->addPropertyMap(new Db\Text('uid'));
            $this->dbMap->addPropertyMap(new Db\Text('username'));
            $this->dbMap->addPropertyMap(new Db\Text('password'));
            $this->dbMap->addPropertyMap(new Db\Text('name'));
            $this->dbMap->addPropertyMap(new Db\Text('email'));
            $this->dbMap->addPropertyMap(new Db\Text('phone'));
            $this->dbMap->addPropertyMap(new Db\Text('image'));
            $this->dbMap->addPropertyMap(new Db\Text('notes'));
            $this->dbMap->addPropertyMap(new Db\Boolean('active'));
            $this->dbMap->addPropertyMap(new Db\Date('lastLogin', 'last_login'));
            $this->dbMap->addPropertyMap(new Db\Text('sessionId', 'session_id'));
            $this->dbMap->addPropertyMap(new Db\Text('hash'));
            $this->dbMap->addPropertyMap(new Db\Date('modified'));
            $this->dbMap->addPropertyMap(new Db\Date('created'));

        }
        return $this->dbMap;
    }

    /**
     *
     * @return \Tk\DataMap\DataMap
     */
    public function getFormMap()
    {
        if (!$this->formMap) {
            $this->formMap = new \Tk\DataMap\DataMap();
            $this->formMap->addPropertyMap(new Form\Integer('id'), 'key');
            $this->formMap->addPropertyMap(new Form\Integer('roleId'));
            $this->formMap->addPropertyMap(new Form\Text('uid'));
            $this->formMap->addPropertyMap(new Form\Text('username'));
            $this->formMap->addPropertyMap(new Form\Text('password'));
            $this->formMap->addPropertyMap(new Form\Text('name'));
            $this->formMap->addPropertyMap(new Form\Text('email'));
            $this->formMap->addPropertyMap(new Form\Text('phone'));
            $this->formMap->addPropertyMap(new Form\Text('image'));
            $this->formMap->addPropertyMap(new Form\Text('notes'));
            $this->formMap->addPropertyMap(new Form\Boolean('active'));
        }
        return $this->formMap;
    }

    /**
     * @param string|int $identity
     * @return \Tk\Db\Map\Model|User
     * @throws \Exception
     */
    public function findByAuthIdentity($identity)
    {
        return $this->findByUsername($identity);
    }

    /**
     * @param $username
     * @return \Tk\Db\Map\Model|User
     * @throws \Exception
     */
    public function findByUsername($username)
    {
        $result = $this->select('username = ' . $this->getDb()->quote($username) );
        return $result->current();
    }

    /**
     * @param $email
     * @return \Tk\Db\Map\Model|User
     * @throws \Exception
     */
    public function findByEmail($email)
    {
        return $this->select('email = ' . $this->getDb()->quote($email))->current();
    }

    /**
     * @param $hash
     * @return \Tk\Db\Map\Model|User
     * @throws \Exception
     */
    public function findByHash($hash)
    {
        return $this->select('hash = ' . $this->getDb()->quote($hash))->current();
    }

    /**
     * @param array $filter
     * @param Tool $tool
     * @return ArrayObject|User[]
     * @throws \Exception
     */
    public function findFiltered($filter = array(), $tool = null)
    {
        $this->makeQuery($filter, $tool, $where, $from);
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
            $w .= sprintf('a.uid LIKE %s OR ', $this->quote($kw));
            $w .= sprintf('a.name LIKE %s OR ', $this->quote($kw));
            $w .= sprintf('a.username LIKE %s OR ', $this->quote($kw));
            $w .= sprintf('a.email LIKE %s OR ', $this->quote($kw));
            $w .= sprintf('a.phone LIKE %s OR ', $this->quote($kw));
            if (is_numeric($filter['keywords'])) {
                $id = (int)$filter['keywords'];
                $w .= sprintf('a.id = %d OR ', $id);
            }
            if ($w) {
                $where .= '(' . substr($w, 0, -3) . ') AND ';
            }
        }

//        if (!empty($filter['roleId'])) {
//            $where .= sprintf('a.role_id = %s AND ', (int)$filter['roleId']);
//        }
        if (!empty($filter['roleId'])) {
            $w = $this->makeMultiQuery($filter['roleId'], 'a.role_id');
            if ($w) {
                $where .= '('. $w . ') AND ';
            }
        }

        if (!empty($filter['username'])) {
            $where .= sprintf('a.username = %s AND ', $this->getDb()->quote($filter['username']));
            if (!empty($filter['password'])) { // ??? Is this insecure ???
                $where .= sprintf('a.password = %s AND ', $this->getDb()->quote($filter['password']));
            }
        }

        if (!empty($filter['active'])) {
            $where .= sprintf('a.active = %s AND ', (int)$filter['active']);
        }

        if (!empty($filter['email'])) {
            $where .= sprintf('a.email = %s AND ', $this->quote($filter['email']));
        }


        if (!empty($filter['role']) && empty($filter['type'])) {
            $filter['type'] = $filter['role'];
        }
        if (!empty($filter['type'])) {
            $from .= sprintf(', user_role d');
            $w = $this->makeMultiQuery($filter['type'], 'd.type');
            if ($w) {
                $where .= 'a.role_id = d.id AND ('. $w . ') AND ';
            }
        }

        if (!empty($filter['exclude'])) {
            $w = $this->makeMultiQuery($filter['exclude'], 'a.id', 'AND', '!=');
            if ($w) {
                $where .= '('. $w . ') AND ';
            }
        }
        
        if ($where) {
            $where = substr($where, 0, -4);
        }

        return $this;
    }

}