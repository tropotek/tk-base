<?php
namespace Bs\Db;


/**
 * Use this interface if you are planing on using the file upload field
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2018 Michael Mifsud
 */
interface FileIface
{

    /**
     * EG:
     *   public function getFileList(string $label = '', ?Tool $tool = null)
     *   {
     *       $filter = ['model' => $this];
     *       if ($label) $filter['label'] = $label;
     *       return \Bs\Db\FileMap::create()->findFiltered($filter, $tool);
     *   }
     *
     * @param string $label     (optional) If supplied the list should only return these labelled files
     * @param \Tk\Db\Tool|null $tool   (optional) If supplied use this in the qurery.
     * @return array|Tk\Db\Map\ArrayObject|File[]
     */
    public function getFileList(string $label = '', ?\Tk\Db\Tool $tool = null);

}
