<?php
namespace Bs\Db\Traits;

use Tk\Db\Map\Model;
use Tk\Db\ModelInterface;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2019 Michael Mifsud
 */
trait ForegnModelTrait
{
    use ForegnKeyTrait;

    /**
     * @var ModelInterface
     */
    private $_model = null;


    /**
     * @return int
     */
    public function getFid()
    {
        return $this->fid;
    }

    /**
     * @param int $fid
     * @return ForegnKeyTrait
     */
    public function setFid($fid)
    {
        $this->fid = $fid;
        return $this;
    }


    /**
     *
     * Note: This is use as an alias incases where set{Object}()
     *   is already used in the main object for another reason
     *
     * @param Model|ModelInterface $model
     * @return ForegnKeyTrait
     */
    public function setModel($model)
    {
        $this->setFkey(get_class($model));
        $this->setFid($model->getVolatileId());
        $this->_model = $model;
        return $this;
    }


    /**
     *
     * Note: This is use as an alias incases where get{Object}()
     *   is already used in the main object for another reason
     *
     * @return null|Model|ModelInterface
     * @throws \Exception
     */
    public function getModel()
    {
        if (!$this->_model && class_exists($this->getFkey().'Map')) {
            $this->_model = $this->getModelMapper()->find($this->getFid());
        }
        return $this->_model;
    }


    /**
     * @param Model|ModelInterface $model
     * @return ForegnKeyTrait
     * @deprecated Use setModel()
     */
    public function setModelObj($model)
    {
        return $this->setModel($model);
    }


    /**
     *
     * @return null|Model|ModelInterface
     * @throws \Exception
     * @deprecated Use getModel()
     */
    public function getModelObj()
    {
        return $this->getModel();
    }


    /**
     * @param array $errors
     * @return array
     */
    public function validateModelId($errors = [])
    {
        $errors = $this->validateFkey($errors);
        if ($this->getFid() === '' || $this->getFid() === null) {
            $errors['fid'] = 'Invalid value: fid';
        }
        return $errors;
    }


}