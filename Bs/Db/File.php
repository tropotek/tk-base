<?php
namespace Bs\Db;

use Bs\Db\Traits\CreatedTrait;
use Bs\Db\Traits\ForeignModelTrait;
use Tk\Exception;
use Tk\Log;
use Tk\Uri;
use Tt\Db;
use Tt\DbFilter;
use Tt\DbModel;

class File extends DbModel
{
    use ForeignModelTrait;
    use CreatedTrait;

    public int        $fileId   = 0;
    public int        $userId   = 0;
    public string     $fkey     = '';
    public int        $fid      = 0;
    public string     $path     = '';
    public int        $bytes    = 0;
    public string     $mime     = '';
    public string     $label    = '';
    public string     $notes    = '';
    public bool       $selected = false;
    public string     $hash     = '';
    public ?\DateTime $created  = null;

    /**
     * @deprecated
     */
    private string    $dataPath = '';


    public function __construct()
    {
        $this->_CreatedTrait();
        $this->dataPath = $this->getConfig()->getDataPath();
    }

    /**
     * Create a File object form an existing file path
     * Only the relative path from the system data path is stored (not the full path)
     *
     * @param string $file Full/Relative data path to a valid file
     */
    public static function create(string $file, ?DbModel $model = null, int $userId = 0): static
    {
        if (empty($file)) {
            throw new Exception('Invalid file path.');
        }

        $obj = new static();

        $obj->path = $file;
        if (str_starts_with($file, $obj->getDataPath())) {
            $obj->path = str_replace($obj->getDataPath(), '', $file);
        }
        if (str_starts_with($file, $obj->getConfig()->get('path.data'))) {
            $obj->path = str_replace($obj->getDataPath(), '', $file);
        }

        $obj->label = \Tk\FileUtil::removeExtension(basename($file));
        if ($model) {
            $obj->setForeignModel($model);
        }
        if (!$userId) {
            if ($model && property_exists($model, 'userId')) {
                $userId = $model->userId;
            } else if ($obj->getFactory()->getAuthUser()) {
                $userId = $obj->getFactory()->getAuthUser()->userId;
            }
        }
        $obj->userId = $userId;

        if (is_file($obj->getFullPath())) {
            $obj->bytes = filesize($obj->getFullPath());
            $obj->mime = \Tk\FileUtil::getMimeType($obj->getFullPath());
        }

        return $obj;
    }

    public function save(): void
    {
        self::install();

        $map = static::getDataMap();
        $values = $map->getArray($this);

        if ($this->fileId) {
            $values['file_id'] = $this->fileId;
            Db::update('file', 'file_id', $values);
        } else {
            unset($values['file_id']);
            Db::insert('file', $values);
            $this->fileId = Db::getLastInsertId();

            // TODO: consider moving hashing generation to the view
            if (empty($this->hash)) {
                $this->hash = self::createHash($this);
                Db::update('file', 'file_id', ['file_id' => $this->fileId, 'hash' => $this->hash]);
            }
        }

        $this->reload();
    }

    public function delete(): bool
    {
        if (is_file($this->getFullPath())) {
            unlink($this->getFullPath());
            Log::alert('File deleted: ' . $this->path);
        }
        return (false !== Db::delete('file', ['file_id' => $this->fileId]));
    }

    public static function createHash(File $file): string
    {
        $key = sprintf('%s%s', $file->fileId, 'File');
        return hash('md5', $key);
    }

    public function getFullPath(): string
    {
        return $this->getDataPath() . $this->path;
    }

    public function getUrl(): Uri
    {
        return Uri::create($this->getConfig()->getDataUrl() . $this->path);
    }

    public function isImage(): bool
    {
        return preg_match('/^image\//', $this->mime);
    }

    /**
     * @deprecated get this from the config `$config->getDataPath()`
     */
    public function getDataPath(): string
    {
        return $this->dataPath;
    }

    public function validate(): array
    {
        $errors = [];

        if (!$this->path) {
            $errors['path'] = 'Please enter a valid path';
        }
        if (!$this->bytes) {
            $errors['bytes'] = 'Please enter a file size';
        }
        if (!$this->mime) {
            $errors['mime'] = 'Please enter a file type';
        }

        $hashed = self::findByHash($this->hash);
        if ($hashed && $hashed->fileId != $this->fileId) {
            $errors['duplicate'] = 'Cannot overwrite an existing file. [ID: ' . $hashed->fileId . ']';
        }

        return $errors;
    }

    public static function find(int $fileId): ?static
    {
        self::install();
        return Db::queryOne("
            SELECT *
            FROM file
            WHERE file_id = :fileId",
            compact('fileId'),
            self::class
        );
    }

    public static function findAll(): ?static
    {
        self::install();
        return Db::queryOne("
            SELECT *
            FROM file",
            [],
            self::class
        );
    }

    public static function findByHash(string $hash): ?static
    {
        return self::findFiltered(['hash' => $hash])[0] ?? null;
    }

    public static function findByPath(string $path): ?static
    {
        return self::findFiltered(['path' => $path])[0] ?? null;
    }

    public static function findFiltered(array|DbFilter $filter): array
    {
        self::install();

        $filter = DbFilter::create($filter);

        if (!empty($filter['search'])) {
            $filter['search'] = '%' . $filter['search'] . '%';
            $w  = 'LOWER(a.file_id) LIKE LOWER(:search) OR ';
            $w .= 'LOWER(a.path) LIKE LOWER(:search) OR ';
            $w .= 'LOWER(a.mime) LIKE LOWER(:search) OR ';
            if ($w) $filter->appendWhere('(%s) AND ', substr($w, 0, -3));
        }

        if (!empty($filter['id'])) {
            $filter['fileId'] = $filter['id'];
        }
        if (!empty($filter['fileId'])) {
            if (!is_array($filter['fileId'])) $filter['fileId'] = [$filter['fileId']];
            $filter->appendWhere('a.file_id IN :fileId AND ', $filter['fileId']);
        }

        if (!empty($filter['exclude'])) {
            if (!is_array($filter['exclude'])) $filter['exclude'] = [$filter['exclude']];
            $filter->appendWhere('a.file_id NOT IN %s AND ', $filter['exclude']);
        }

        if (isset($filter['label'])) {
            if (!is_array($filter['label'])) $filter['label'] = [$filter['label']];
            $filter->appendWhere('a.label IN :label AND ', $filter['label']);
        }
        if (isset($filter['mime'])) {
            if (!is_array($filter['mime'])) $filter['mime'] = [$filter['mime']];
            $filter->appendWhere('a.mime IN :mime AND ', $filter['mime']);
        }

        if (is_bool($filter['selected'])) {
            $filter->appendWhere('a.selected = :selected AND ');
        }

        if (!empty($filter['path'])) {
            $filter->appendWhere('a.path = :path AND ');
        }
        if (!empty($filter['hash'])) {
            $filter->appendWhere('a.hash = :hash AND ');
        }

        if (!empty($filter['model']) && $filter['model'] instanceof DbModel) {
            $filter['fid'] = self::getModelId($filter['model']);
            $filter['fkey'] = get_class($filter['model']);
        }
        if (isset($filter['fid'])) {
            $filter->appendWhere('a.fid = :fid AND ');
        }
        if (isset($filter['fkey'])) {
            $filter->appendWhere('a.fkey = :fkey AND ');
        }

        return Db::query("
            SELECT *
            FROM file a
            {$filter->getSql()}",
            $filter->all(),
            self::class
        );
    }

    public static function install(): bool
    {
        // TODO: note getting a PdoSessionHandler.php error when this is run???
        //       Not sure why, maybe having a non symfony DB session handler will be better
        if (!Db::tableExists('file')) {
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
            Db::execute($sql);
            return true;
        }
        return false;
    }

    public static function getIcon(string $filename): string
    {
        $ext = \Tk\FileUtil::getExtension($filename);
        switch ($ext) {
            case 'zip':
            case 'gz':
            case 'tar':
            case 'tar.gz':
            case 'gtz':
            case 'rar':
            case '7zip':
            case 'jar':
            case 'pkg':
            case 'deb':
                return 'fa fa-file-archive-o';
            case 'h':
            case 'c':
            case 'php':
            case 'js':
            case 'css':
            case 'less':
            case 'txt':
            case 'xml':
            case 'xslt':
            case 'json':
                return 'fa fa-file-code-o';
            case 'ods':
            case 'sdc':
            case 'sxc':
            case 'xls':
            case 'xlsm':
            case 'xlsx':
            case 'csv':
                return 'fa fa-file-excel-o';
            case 'bmp':
            case 'emf':
            case 'gif':
            case 'ico':
            case 'icon':
            case 'jpeg':
            case 'jpg':
            case 'pcx':
            case 'pic':
            case 'png':
            case 'psd':
            case 'raw':
            case 'tga':
            case 'tif':
            case 'tiff':
            case 'swf':
            case 'drw':
            case 'svg':
            case 'svgz':
            case 'ai':
                return 'fa fa-file-image-o';
            case 'aiff':
            case 'cda':
            case 'dvf':
            case 'flac':
            case 'm4a':
            case 'm4b':
            case 'midi':
            case 'mp3':
            case 'ogg':
            case 'pcm':
            case 'snd':
            case 'wav':
                return 'fa fa-file-audio-o';
            case 'avi':
            case 'mov':
            case 'mp4':
            case 'mpg':
            case 'mpeg':
            case 'mkv':
            case 'ogv':
            case 'flv':
            case 'webm':
            case 'wmv':
            case 'asx':
                return 'fa fa-file-video-o';
            case 'pdf':
                return 'fa fa-file-pdf-o';
            case 'ppt':
            case 'pot':
            case 'potx':
            case 'pps':
            case 'ppsx':
            case 'pptx':
            case 'pptm':
                return 'fa fa-file-powerpoint-o';
            case 'doc':
            case 'docm':
            case 'dotm':
            case 'dotx':
            case 'docx':
            case 'dot':
            case 'wri':
            case 'wps':
                return 'fa fa-file-word-o';
        }
        return 'fa fa-file-o';
    }
}