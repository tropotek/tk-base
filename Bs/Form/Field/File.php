<?php
namespace Bs\Form\Field;

use Tt\DbModel;

/**
 * Use this field in conjunction with the \Bs\Db\File object
 */
class File extends \Tk\Form\Field\File
{
    /**
     * The file owner object that will be used as the fkey and fid for the file records
     */
    protected DbModel $model;

    protected bool $enableSelect = false;


    public function __construct(string $name, DbModel $model = null)
    {
        parent::__construct($name);
        $this->model = $model;

        $this->setAttr('multiple', 'multiple');
        $this->setAttr('data-uploader', self::class);
        //$this->addCss('tk-multiinput');
    }

    public static function createFile($name, DbModel $model): static
    {
        return new static($name, $model);
    }

    /**
     * This is called only once the form has been submitted
     *   and new data loaded into the fields
     */
    public function execute(array $values = []): static
    {
        if ($this->hasFile()) {
            foreach ($this->getUploads() as $file) {
                $dest = $this->getConfig()->getDataPath() . $this->getModel()->getDataPath() . '/' . $file['name'];
                move_uploaded_file($file['tmp_name'], dirname($dest)."/".basename($dest));

                $file = \Bs\Db\File::create($dest, $this->getModel());
                // Remove any existing File if path matches
                $exists = \Bs\Db\File::findByPath($file->path);
                $exists?->delete();
                $file->save();
            }
        }
        return $this;
    }

    public function getModel(): ?DbModel
    {
        return $this->model;
    }
}