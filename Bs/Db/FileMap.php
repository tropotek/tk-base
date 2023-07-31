<?php
namespace Bs\Db;

use Tk\DataMap\DataMap;
use Tk\DataMap\Db;
use Tk\DataMap\Form;
use Tk\DataMap\Table;
use Tk\Db\Mapper\Filter;
use Tk\Db\Mapper\Mapper;
use Tk\Db\Mapper\ModelInterface;
use Tk\Db\Pdo;

class FileMap extends Mapper
{

    public function __construct(?Pdo $db = null)
    {
        parent::__construct($db);
        if (!$this->getDb()->hasTable('file')) {
            $sql = <<<SQL
CREATE TABLE IF NOT EXISTS file
(
    file_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
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
    created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY user_id (user_id),
    KEY fkey (fkey),
    KEY fkey_2 (fkey, fid),
    KEY fkey_3 (fkey, fid, label)
);
SQL;
            $this->getDb()->exec($sql);
        }
    }

    public function makeDataMaps(): void
    {
        if (!$this->getDataMappers()->has(self::DATA_MAP_DB)) {
            $map = new DataMap();
            $map->addDataType(new Db\Integer('fileId', 'file_id'));
            $map->addDataType(new Db\Integer('userId', 'user_id'));
            $map->addDataType(new Db\Text('fkey'));
            $map->addDataType(new Db\Integer('fid'));
            $map->addDataType(new Db\Text('path'));
            $map->addDataType(new Db\Integer('bytes'));
            $map->addDataType(new Db\Text('mime'));
            $map->addDataType(new Db\Text('label'));
            $map->addDataType(new Db\Text('notes'));
            $map->addDataType(new Db\Text('hash'));
            $map->addDataType(new Db\Boolean('selected'));
            $map->addDataType(new Db\Date('created'));
            $this->addDataMap(self::DATA_MAP_DB, $map);
        }

        if (!$this->getDataMappers()->has(self::DATA_MAP_FORM)) {
            $map = new DataMap();
            $map->addDataType(new Form\Integer('fileId'));
            $map->addDataType(new Form\Integer('userId'));
            $map->addDataType(new Form\Text('fkey'));
            $map->addDataType(new Form\Integer('fid'));
            $map->addDataType(new Form\Text('path'));
            $map->addDataType(new Form\Integer('bytes'));
            $map->addDataType(new Form\Text('mime'));
            $map->addDataType(new Form\Text('label'));
            $map->addDataType(new Form\Text('notes'));
            $map->addDataType(new Form\Boolean('selected'));
            $this->addDataMap(self::DATA_MAP_FORM, $map);
        }

        if (!$this->getDataMappers()->has(self::DATA_MAP_TABLE)) {
            $map = new DataMap();
            $map->addDataType(new Form\Integer('fileId'));
            $map->addDataType(new Form\Integer('userId'));
            $map->addDataType(new Form\Text('fkey'));
            $map->addDataType(new Form\Integer('fid'));
            $map->addDataType(new Form\Text('path'));
            $map->addDataType(new Form\Integer('bytes'));
            $map->addDataType(new Form\Text('mime'));
            $map->addDataType(new Form\Text('label'));
            $map->addDataType(new Form\Text('notes'));
            $map->addDataType(new Table\Boolean('selected'));
            $map->addDataType(new Form\Date('created'))->setDateFormat('d/m/Y h:i:s');
            $this->addDataMap(self::DATA_MAP_TABLE, $map);
        }
    }

    public function findByHash($hash): ?File
    {
        return $this->findFiltered(['hash' => $hash])->current();
    }

    /**
     * @return \Tk\Db\Mapper\Result | File[]
     */
    public function findFiltered($filter, $tool = null): \Tk\Db\Mapper\Result
    {
        return $this->prepareFromFilter($this->makeQuery(Filter::create($filter)), $tool);
    }

    public function makeQuery(Filter $filter): Filter
    {
        $filter->appendFrom('%s a ', $this->quoteParameter($this->getTable()));

        if (!empty($filter['search'])) {
            $filter['search'] = '%' . $this->getDb()->escapeString($filter['search']) . '%';
            $w  = 'a.file_id LIKE :search OR ';
            $w .= 'a.path LIKE :search OR ';
            $w .= 'a.mime LIKE :search OR ';
            if ($w) $filter->appendWhere('(%s) AND ', substr($w, 0, -3));
        }

        if (!empty($filter['id'])) {
            $filter['fileId'] = $filter['id'];
        }
        if (!empty($filter['fileId'])) {
            $filter->appendWhere('(a.file_id IN (:userId)) AND ');
        }

        if (isset($filter['label'])) {
            $filter->appendWhere('(a.label IN (:label)) AND ');
        }
        if (isset($filter['mime'])) {
            $filter->appendWhere('(a.mime IN (:mime)) AND ');
        }

        if (!$this->isEmpty($filter['selected'] ?? null)) {
            $filter->appendWhere('a.selected = :selected AND ');
        }

        if (!empty($filter['path'])) {
            $filter->appendWhere('a.path = :path AND ');
        }
        if (!empty($filter['hash'])) {
            $filter->appendWhere('a.hash = :hash AND ');
        }

        if (!empty($filter['model']) && $filter['model'] instanceof ModelInterface) {
            $filter['fid'] = $filter['model']->getId();
            $filter['fkey'] = get_class($filter['model']);
        }
        if (isset($filter['fid'])) {
            $filter->appendWhere('a.fid = :fid AND ');
        }
        if (isset($filter['fkey'])) {
            $filter->appendWhere('a.fkey = :fkey AND ');
        }

        if (!empty($filter['exclude'])) {
            $filter->appendWhere('(a.file_id NOT IN (:exclude)) AND ');
        }

        return $filter;
    }

}