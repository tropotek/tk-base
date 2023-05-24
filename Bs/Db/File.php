<?php
namespace Bs\Db;

use Bs\Db\Traits\ForeignModelTrait;
use Bs\Db\Traits\TimestampTrait;
use Bs\Db\Traits\UserTrait;
use Tk\Db\Mapper\Model;
use Tk\Db\Mapper\ModelInterface;
use Tk\Config;
use Tk\Log;
use Tk\Uri;
use DateTime;

class File extends Model
{

    use ForeignModelTrait;
    use TimestampTrait;
    use UserTrait;

    public int $id = 0;

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

    public ?DateTime $modified = null;

    public ?DateTime $created = null;


    public function __construct()
    {
        $this->_TimestampTrait();
    }

    /**
     * @param string $file     Relative/Full path to a valid file
     * @param string $dataPath (optional) if none then \App\Config::getInstance()->getDataPath() is used
     */
    public static function create(ModelInterface $model, string $file = '', string $dataPath = '', int $userId = 0): static
    {
        $obj = new static();
        $obj->setLabel(\Tk\FileUtil::removeExtension(basename($file)));
        $obj->setForeignModel($model);
        if (!$userId) {
            if (method_exists($model, 'getUserId')) {
                $userId = $model->getUserId();
            } elseif (property_exists($model, 'userId')) {
                $userId = $model->userId;
            } else if ($obj->getFactory()->getAuthUser()) {
                $userId = $obj->getFactory()->getAuthUser()->getId();
            }
        }
        $obj->setUserId($userId);

        if (!$dataPath) $dataPath = Config::instance()->getDataPath();
        if ($file) {
            $file = str_replace($dataPath, '', $file);
            $fullPath = $dataPath . $file;
            if (is_file($fullPath)) {
                $obj->setPath($file);
                $obj->setBytes(filesize($fullPath));
                $obj->setMime(\Tk\FileUtil::getMimeType($fullPath));
            }
        }
        return $obj;
    }

    public function save(): void
    {
        $this->getHash();
        parent::save();
    }

    public function delete(string $dataPath = ''): int
    {
        if (!$dataPath) $dataPath = $this->getConfig()->getDataPath();
        if ($dataPath && is_file($dataPath . $this->getPath())) {
            unlink($dataPath . $this->getPath());
            Log::alert('File deleted: ' . $dataPath . $this->getPath());
        } else {
            Log::warning('File not deleted: ' . $dataPath . $this->getPath());
        }
        return parent::delete();
    }

    public function getHash(): string
    {
        if (!$this->hash) {
            $this->hash = $this->generateHash();
        }
        return $this->hash;
    }

    public function getUrl(): Uri
    {
        return Uri::create($this->getConfig()->getDataUrl() . $this->getPath());
    }

    public function generateHash(): string
    {
        return hash('md5', sprintf('%s%s%s', $this->getFkey(), $this->getFid(), $this->getPath()));
    }

    public function getIcon(): string
    {
        $ext = \Tk\FileUtil::getExtension($this->path);
        switch ($ext) {
            case 'zip':
            case 'gz':
            case 'tar':
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

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): static
    {
        $this->path = $path;
        return $this;
    }

    public function getBytes(): int
    {
        return $this->bytes;
    }

    public function setBytes(int $bytes): static
    {
        $this->bytes = $bytes;
        return $this;
    }

    public function getMime(): string
    {
        return $this->mime;
    }

    public function setMime(string $mime): static
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

    public function setLabel(string $label): static
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

    public function setSelected(bool $selected): static
    {
        $this->selected = $selected;
        return $this;
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
}