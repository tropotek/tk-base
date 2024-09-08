<?php
namespace Bs\Db\Traits;

use Tk\Exception;
use Tk\Db\Model;

trait ForeignModelTrait
{

    private ?Model $_model = null;


    /**
     * Alias to setForeignModel();
     */
    public function setModel(Model $model): static
    {
        return $this->setForeignModel($model);
    }

    public function setForeignModel(Model $model): static
    {
        $mid = self::getModelId($model);
        if (!$mid) throw new Exception("Model ID not set");
        $this->fkey = get_class($model);
        $this->fid = $mid;
        $this->_model = $model;
        return $this;
    }

    /**
     * Alias to getForeignModel();
     */
    public function getModel(): ?Model
    {
        return $this->getForeignModel();
    }

    public function getForeignModel(): ?Model
    {
        if (method_exists($this->fkey, 'find')) {
            $this->_model = $this->fkey::find($this->fkey);
        }
        return $this->_model;
    }

    protected static function getModelId(Model $model): int
    {
        $map = $model->getDataMap();
        $priKey = $map->getPrimaryKey()?->getProperty();
        return intval($model?->$priKey);
    }

    /**
     * @deprecated Note sure this is needed here???
     */
    public function validateModelId(array $errors = []): array
    {
//        $errors = $this->validateFkey($errors);
//        if ($this->getFid()) {
//            $errors['fid'] = 'Invalid value: fid';
//        }
        return [];
    }

}