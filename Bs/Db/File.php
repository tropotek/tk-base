<?php
namespace Bs\Db;

use Bs\Db\Traits\CreatedTrait;
use Bs\Db\Traits\ForeignModelTrait;
use Bs\Db\Traits\HashTrait;
use Tk\Db\Mapper\Model;
use Tk\Db\Mapper\ModelInterface;
use Tk\Exception;
use Tk\Log;
use Tk\Uri;
use DateTime;

class File extends Model
{
    use ForeignModelTrait;
    use CreatedTrait;
    use HashTrait;

    public int $fileId = 0;

    public int $userId = 0;

    public string $fkey = '';

    public int $fid = 0;

    public string $path = '';

    public int $bytes = 0;

    public string $mime = '';

    public string $label = '';

    public string $notes = '';

    public bool $selected = false;

    public string $hash = '';

    public ?DateTime $created = null;

    private string $dataPath = '';


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
    public static function create(string $file, ?ModelInterface $model = null, int $userId = 0): static
    {
        if (empty($file)) {
            throw new Exception('Invalid file path.');
        }

        $obj = new static();
        $obj->setPath($file);
        $obj->setLabel(\Tk\FileUtil::removeExtension(basename($file)));
        if ($model) {
            $obj->setForeignModel($model);
        }
        if (!$userId) {
            if ($model && method_exists($model, 'getUserId')) {
                $userId = $model->getUserId();
            } elseif ($model && property_exists($model, 'userId')) {
                $userId = $model->userId;
            } else if ($obj->getFactory()->getAuthUser()) {
                $userId = $obj->getFactory()->getAuthUser()->getId();
            }
        }
        $obj->setUserId($userId);

        if (is_file($obj->getFullPath())) {
            $obj->setBytes(filesize($obj->getFullPath()));
            $obj->setMime(\Tk\FileUtil::getMimeType($obj->getFullPath()));
        }

        return $obj;
    }

    public function save(): void
    {
        $this->getHash();
        parent::save();
    }

    public function delete(): int
    {
        if (is_file($this->getFullPath())) {
            unlink($this->getFullPath());
            Log::alert('File deleted: ' . $this->getPath());
        }
        return parent::delete();
    }

    public function generateHash(): string
    {
        return hash('md5', sprintf('%s%s%s', $this->getFkey(), $this->getFid(), $this->getPath()));
    }

    public function getFileId(): int
    {
        return $this->fileId;
    }

    public function setFileId(int $fileId): File
    {
        $this->fileId = $fileId;
        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): File
    {
        $this->userId = $userId;
        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getFullPath(): string
    {
        return $this->getDataPath() . $this->getPath();
    }

    public function getUrl(): Uri
    {
        return Uri::create($this->getConfig()->getDataUrl() . $this->getPath());
    }

    protected function setPath(string $path): static
    {
        // Clean the full path if supplied
        if (str_starts_with($path, $this->getDataPath())) {
            $this->path = str_replace($this->getDataPath(), '', $path);
        }
        if (str_starts_with($path, $this->getConfig()->get('path.data'))) {
            $this->path = str_replace($this->getDataPath(), '', $path);
        }
        return $this;
    }

    public function getBytes(): int
    {
        return $this->bytes;
    }

    protected function setBytes(int $bytes): static
    {
        $this->bytes = $bytes;
        return $this;
    }

    public function getMime(): string
    {
        return $this->mime;
    }

    protected function setMime(string $mime): static
    {
        $this->mime = $mime;
        return $this;
    }

    public function isImage(): bool
    {
        return preg_match('/^image\//', $this->getMime());
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    protected function setLabel(string $label): static
    {
        $this->label = $label;
        return $this;
    }

    public function getNotes(): string
    {
        return $this->notes;
    }

    public function setNotes(string $notes): static
    {
        $this->notes = $notes;
        return $this;
    }

    public function isSelected(): bool
    {
        return $this->selected;
    }

    protected function setSelected(bool $selected): static
    {
        $this->selected = $selected;
        return $this;
    }

    public function getDataPath(): string
    {
        return $this->dataPath;
    }

    public function validate(): array
    {
        $errors = [];

        if (!$this->getPath()) {
            $errors['path'] = 'Please enter a valid path';
        }
        if (!$this->getBytes()) {
            $errors['bytes'] = 'Please enter a file size';
        }
        if (!$this->getMime()) {
            $errors['mime'] = 'Please enter a file type';
        }

        $hashed = FileMap::create()->findByHash($this->getHash());
        if ($hashed && $hashed->getId() != $this->getVolatileId()) {
            $errors['duplicate'] = 'Cannot overwrite an existing file. [ID: ' . $hashed->getId() . ']';
        }

        return $errors;
    }

    public static function getIcon($file): string
    {
        $ext = \Tk\FileUtil::getExtension($file);
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