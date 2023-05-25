<?php
namespace Bs\Form\Field;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Tk\Form\Exception;

/**
 * Use this field in conjunction with the \Bs\Db\File object
 */
class File extends \Tk\Form\Field\File
{
    /**
     * The file owner object that will be used as the fkey and fid for the file records
     */
    protected ?\Bs\Db\FileInterface $model = null;

    protected bool $enableSelect = false;


    public function __construct(string $name, ?\Bs\Db\FileInterface $model = null)
    {
        parent::__construct($name);
        $this->model = $model;

        $this->setAttr('multiple', 'multiple');
        $this->setAttr('data-uploader', self::class);
        $this->addCss('tk-multiinput');

        if ($this?->model->getId()) {
            $files = $this->model->getFileList()->toArray();
            usort($files, function ($a, $b) {
                return $a->getLabel() <=> $b->getLabel();
            });
            $v = json_encode($files);
            $this->setAttr('data-value', $v);
            $this->setAttr('data-enable-select', 'true');
            $this->setAttr('data-prop-path', 'path');
            $this->setAttr('data-prop-id', 'id');
        }
    }

    public static function createFile($name, ?\Bs\Db\FileInterface $owner = null): static
    {
        return new static($name, $owner);
    }

    /**
     * This is called only once the form has been submitted
     *   and new data loaded into the fields
     */
    public function execute(array $values = []): void
    {
        $request = $this->getRequest();
//        if ($request->get($this->getName() . '_lst')) $this->onGetList($request);
//        if ($request->get($this->getName() . '_del')) $this->onDelete($request);
//        if ($request->get($this->getName() . '_sel')) $this->onSelect($request);
    }

    /**
     * Call this method to process and save uploaded files
     *
     * @return $this
     * @todo: Add a method addSubmit(callable...) queue to the field
     */
    public function doSubmit()
    {
//        if ($this->getForm()->isSubmitted() && $this->isValid()) {
//            if ($this->hasFile()) {
//                /** @var UploadedFile $uploadedFile */
//                foreach ($this->getUploadedFiles() as $uploadedFile) {
//                    // TODO: We could put this in its on doValidate method?
//                    if (!\App\Config::getInstance()->validateFile($uploadedFile->getClientOriginalName())) {
//                        \Tk\Alert::addWarning('Illegal file type: ' . $uploadedFile->getClientOriginalName());
//                        continue;
//                    }
//                    try {
//                        $filePath = $this->getConfig()->getDataPath() . $this->getOwner()->getDataPath() . '/' . $uploadedFile->getClientOriginalName();
//                        if (!is_dir(dirname($filePath))) {
//                            mkdir(dirname($filePath), $this->getConfig()->getDirMask(), true);
//                        }
//                        $uploadedFile->move(dirname($filePath), basename($filePath));
//                        $oFile = \App\Db\FileMap::create()->findFiltered(array('model' => $this->getOwner(), 'path' => $this->getOwner()->getDataPath() . '/' . $uploadedFile->getClientOriginalName()))->current();
//                        if (!$oFile) {
//                            $oFile = \App\Db\File::create($this->getOwner(), $this->getOwner()->getDataPath() . '/' . $uploadedFile->getClientOriginalName(), $this->getConfig()->getDataPath() );
//                        }
//                        $oFile->save();
//                    } catch (\Exception $e) {
//                        \Tk\Log::error($e->__toString());
//                        \Tk\Alert::addWarning('Error Uploading file: ' . $uploadedFile->getClientOriginalName());
//                    }
//                }
//            }
//        }

        return $this;
    }

    public function onDelete(Request $request)
    {
//        $fileId = $request->get($this->getName() . '_del');
//        try {
//            /** @var \Bs\Db\File $file */
//            $file = \Bs\Db\FileMap::create()->find($fileId);
//            if ($file) $file->delete();
//            \Tk\ResponseJson::createJson(array('status' => 'ok', 'file' => $file))->send();
//        } catch (\Exception $e) {
//            \Tk\ResponseJson::createJson(array('status' => 'err', 'msg' => $e->getMessage()), 500)->send();
//        }
//        exit();
    }

    public function onSelect(Request $request)
    {
//        $fileId = $request->get($this->getName() . '_sel');
//        try {
//            /** @var \Bs\Db\File $file */
//            $file = \Bs\Db\FileMap::create()->find($fileId);
//            if ($file) {
//                $file->setSelected(!$file->isSelected());
//                $file->save();
//            }
//            \Tk\ResponseJson::createJson(array('status' => 'ok', 'file' => $file))->send();
//        } catch (\Exception $e) {
//            \Tk\ResponseJson::createJson(array('status' => 'err', 'msg' => $e->getMessage()), 500)->send();
//        }
//        exit();
    }

    // TODO: Implement this api call in the tkFileInput.js file
    public function onGetList(Request $request)
    {
//        try {
//            $label = $request->get('label', '');
//            $list = $this->owner->getFileList($label);
//            \Tk\ResponseJson::createJson(array('status' => 'ok', 'fileList' => $list))->send();
//        } catch (\Exception $e) {
//            \Tk\ResponseJson::createJson(array('status' => 'err', 'msg' => $e->getMessage()), 500)->send();
//        }
//        exit();
    }

    public function getModel(): ?\Bs\Db\FileInterface
    {
        return $this->model;
    }
}