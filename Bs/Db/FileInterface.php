<?php
namespace Bs\Db;

use Tk\Db\Mapper\ModelInterface;
use Tk\Db\Mapper\Result;

/**
 * Use this interface in your model objects when using a file upload form field
 * @deprecated
 */
interface FileInterface extends ModelInterface
{

    /**
     * EG:
     *   public function getFileList(string $label = '', ?Tool $tool = null)
     *   {
     *       $filter = ['model' => $this];
     *       if ($label) $filter['label'] = $label;
     *       return \Bs\Db\FileMap::create()->findFiltered($filter, $tool);
     *   }
     */
    public function getFileList(array $filter = [], ?\Tk\Db\Tool $tool = null): Result;

    /**
     * Return the root folder location to save these files
     * The return path should only include the path from the site root data folder
     * EG:
     *     '/user/files' would translate to  '{site_root}/data/user/files
     */
    public function getDataPath(): string;

}
