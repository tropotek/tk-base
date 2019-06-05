<?php
namespace Bs\Db\Traits;

use Tk\Db\Map\Model;
use Tk\Db\ModelInterface;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2019 Michael Mifsud
 */
trait ForegnKeyTrait
{



    /**
     * @return string
     */
    public function getFkey()
    {
        return $this->fkey;
    }

    /**
     * @param strin $fkey
     * @return $this
     */
    public function setFkey($fkey)
    {
        $this->fkey = $fkey;
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
     * @param Model|ModelInterface $model
     * @return ForegnKeyTrait
     */
    public function setModelObj($model)
    {
        if ($model instanceof ModelInterface)
            $this->setFkey(get_class($model));
        if (is_string($model) && class_exists($model))
            $this->setFkey($model);
        return $this;
    }

    /**
     * The models DB mapper object for querieng the DB
     *
     * @return null|\Tk\Db\Map\Mapper
     */
    public function getModelMapper()
    {
        return Model::createMapper($this->getFkey().'Map');
    }

    /**
     *
     * Note: This is use as an alias incases where find{Object}()
     *   is already used in the main object for another reason
     *
     * @param int $modelId
     * @return Model
     * @throws \Exception
     */
    public function findModel($modelId)
    {
        return $this->findModelObj($modelId);
    }

    /**
     * @param int $modelId
     * @return Model
     * @throws \Exception
     */
    public function findModelObj($modelId)
    {
        $mapper = $this->getModelMapper();
        if (!$mapper) return null;
        return $mapper->find($modelId);
    }


    /**
     * @param array $errors
     * @return array
     */
    public function validateFkey($errors = [])
    {
        if (!$this->fkey) {
            $errors['fkey'] = 'Invalid value: fkey';
        }
        return $errors;
    }


}