<?php
namespace Bs\Form\Field;

use Bs\Db\FileMap;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Use this field in conjunction with the \Bs\Db\File object
 */
class File extends \Tk\Form\Field\File
{
    /**
     * The file owner object that will be used as the fkey and fid for the file records
     */
    protected \Bs\Db\FileInterface $model;

    protected bool $enableSelect = false;


    public function __construct(string $name, \Bs\Db\FileInterface $model = null)
    {
        parent::__construct($name);
        $this->model = $model;

        $this->setAttr('multiple', 'multiple');
        $this->setAttr('data-uploader', self::class);
        //$this->addCss('tk-multiinput');
    }

    public static function createFile($name, \Bs\Db\FileInterface $model): static
    {
        return new static($name, $model);
    }

    /**
     * This is called only once the form has been submitted
     *   and new data loaded into the fields
     */
    public function execute(array $values = []): void
    {
        if ($this->hasFile()) {
            /** @var UploadedFile $uploaded */
            foreach ($this->getUploads() as $uploadedFile) {
                $dest = $this->getConfig()->getDataPath() . $this->getModel()->getDataPath() . '/' . $uploadedFile->getClientOriginalName();
                $uploadedFile->move(dirname($dest), basename($dest));

                $file = \Bs\Db\File::create($dest, $this->getModel());
                // Remove any existing File, File objects should not be updated
                $exists = FileMap::create()->findByHash($file->getHash());
                $exists?->delete();
                $file->save();
            }
        }
    }

    public function getModel(): ?\Bs\Db\FileInterface
    {
        return $this->model;
    }
}