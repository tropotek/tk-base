<?php

namespace Bs\Db;

use Bs\Db\Mapper;
use Exception;
use Tk\DataMap\DataMap;
use Tk\DataMap\Db;
use Tk\DataMap\Form;
use Tk\Db\Filter;
use Tk\Db\Map\ArrayObject;
use Tk\Db\Map\Model;
use Tk\Db\Pdo;
use Tk\Db\Tool;

/**
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class FileMap extends Mapper
{

    /**
     * @param \Tk\Db\Pdo|null $db
     * @throws \Exception
     */
    public function __construct($db = null)
    {
        parent::__construct($db);
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS file
(
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT(10) UNSIGNED NOT NULL DEFAULT 0,
    fkey VARCHAR(64) DEFAULT '' NOT NULL,
    fid INT DEFAULT 0 NOT NULL,
    label VARCHAR(128) default '' NOT NULL,
    `path` TEXT NULL,
    bytes INT DEFAULT 0 NOT NULL,
    mime VARCHAR(255) DEFAULT '' NOT NULL,
    notes TEXT NULL,
    selected BOOL NOT NULL DEFAULT FALSE,
    hash VARCHAR(128) DEFAULT '' NOT NULL,
    modified datetime NOT NULL,
    created datetime NOT NULL,
    KEY user_id (user_id),
    KEY fkey (fkey),
    KEY fkey_2 (fkey, fid),
    KEY fkey_3 (fkey, fid, label)
);
SQL;
        if (!$this->getDb()->hasTable('file')) {
            $this->getDb()->exec($sql);
        }
    }

    /**
     * @return DataMap
     */
    public function getDbMap()
    {
        if (!$this->dbMap) {
            $this->dbMap = new DataMap();
            $this->dbMap->addPropertyMap(new Db\Integer('id'), 'key');
            $this->dbMap->addPropertyMap(new Db\Integer('userId', 'user_id'));
            $this->dbMap->addPropertyMap(new Db\Text('fkey'));
            $this->dbMap->addPropertyMap(new Db\Integer('fid'));
            $this->dbMap->addPropertyMap(new Db\Text('path'));
            $this->dbMap->addPropertyMap(new Db\Integer('bytes'));
            $this->dbMap->addPropertyMap(new Db\Text('mime'));
            $this->dbMap->addPropertyMap(new Db\Text('label'));
            $this->dbMap->addPropertyMap(new Db\Text('notes'));
            $this->dbMap->addPropertyMap(new Db\Text('hash'));
            $this->dbMap->addPropertyMap(new Db\Boolean('selected'));
            $this->dbMap->addPropertyMap(new Db\Date('modified'));
            $this->dbMap->addPropertyMap(new Db\Date('created'));
        }
        return $this->dbMap;
    }

    /**
     * @return DataMap
     */
    public function getFormMap()
    {
        if (!$this->formMap) {
            $this->formMap = new DataMap();
            $this->formMap->addPropertyMap(new Form\Integer('id'), 'key');
            $this->formMap->addPropertyMap(new Form\Integer('userId'));
            $this->formMap->addPropertyMap(new Form\Text('fkey'));
            $this->formMap->addPropertyMap(new Form\Integer('fid'));
            $this->formMap->addPropertyMap(new Form\Text('path'));
            $this->formMap->addPropertyMap(new Form\Integer('bytes'));
            $this->formMap->addPropertyMap(new Form\Text('mime'));
            $this->formMap->addPropertyMap(new Form\Text('label'));
            $this->formMap->addPropertyMap(new Form\Text('notes'));
            $this->formMap->addPropertyMap(new Form\Boolean('selected'));
        }
        return $this->formMap;
    }

    /**
     * @param string $hash
     * @return File|Model|null
     * @throws Exception
     */
    public function findByHash($hash)
    {
        return $this->findFiltered(array('hash' => $hash))->current();
    }

    /**
     * @param array|Filter $filter
     * @param Tool $tool
     * @return ArrayObject|File[]
     * @throws Exception
     */
    public function findFiltered($filter, $tool = null)
    {
        return $this->selectFromFilter($this->makeQuery(Filter::create($filter)), $tool);
    }

    /**
     * @param Filter $filter
     * @return Filter
     */
    public function makeQuery(Filter $filter)
    {
        $filter->appendFrom('%s a ', $this->quoteParameter($this->getTable()));

        if (!empty($filter['keywords'])) {
            $kw = '%' . $this->getDb()->escapeString($filter['keywords']) . '%';
            $w = '';
            $w .= sprintf('a.path LIKE %s OR ', $this->quote($kw));
            $w .= sprintf('a.mime LIKE %s OR ', $this->quote($kw));
            if (is_numeric($filter['keywords'])) {
                $id = (int)$filter['keywords'];
                $w .= sprintf('a.id = %d OR ', $id);
            }
            if ($w) $filter->appendWhere('(%s) AND ', substr($w, 0, -3));
        }

        if (isset($filter['id'])) {
            $w = $this->makeMultiQuery($filter['id'], 'a.id');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }
        if (isset($filter['userId'])) {
            $w = $this->makeMultiQuery($filter['userId'], 'a.user_id');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }
        if (isset($filter['label'])) {
            $w = $this->makeMultiQuery($filter['label'], 'a.label');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }
        if (isset($filter['mime'])) {
            $w = $this->makeMultiQuery($filter['mime'], 'a.mime');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (isset($filter['selected']) && $filter['selected'] !== '' && $filter['selected'] !== null) {
            $filter->appendWhere('a.selected = %s AND ', (int)$filter['selected']);
        }

        if (!empty($filter['path'])) {
            $filter->appendWhere('a.path = %s AND ', $this->quote($filter['path']));
        }
        if (!empty($filter['hash'])) {
            $filter->appendWhere('a.hash = %s AND ', $this->quote($filter['hash']));
        }

        if (!empty($filter['model']) && $filter['model'] instanceof Model) {
            $filter['fid'] = $filter['model']->getId();
            $filter['fkey'] = get_class($filter['model']);
        }
        if (isset($filter['fid'])) {
            $filter->appendWhere('a.fid = %d AND ', (int)$filter['fid']);
        }
        if (isset($filter['fkey'])) {
            $filter->appendWhere('a.fkey = %s AND ', $this->quote($filter['fkey']));
        }

        if (!empty($filter['exclude'])) {
            $w = $this->makeMultiQuery($filter['exclude'], 'a.id', 'AND', '!=');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        return $filter;
    }

}