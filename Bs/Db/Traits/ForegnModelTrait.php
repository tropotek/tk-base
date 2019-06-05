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
        return $this->setModelObj($model);
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
        return $this->getModelObj();
    }


    /**
     * @param Model|ModelInterface $model
     * @return ForegnKeyTrait
     */
    public function setModelObj($model)
    {
        $this->setFkey(get_class($model));
        $this->setFid($model->getVolatileId());
        $this->_model = $model;
        return $this;
    }


    /**
     *
     * @return null|Model|ModelInterface
     * @throws \Exception
     */
    public function getModelObj()
    {
        if (!$this->_model && class_exists($this->getFkey().'Map')) {
            $this->_model = $this->getModelMapper()->find($this->getFid());
        }
        return $this->_model;
    }


    /**
     * @param array $errors
     * @return array
     */
    public function validateModelId($errors = [])
    {
        $errors = $this->validateFkey($errors);
        if (!$this->getFid()) {
            $errors['fid'] = 'Invalid value: fid';
        }
        return $errors;
    }


}