<?php
namespace Bs\Traits;

use Tk\Exception;
use Tk\Db\Model;

trait ForeignModelTrait
{

    private ?Model $_model = null;


    public function setDbModel(Model $model): static
    {
        $mid = self::getDbModelId($model);
        if (!$mid) throw new Exception("Db Model ID not set");
        $this->fkey = get_class($model);
        $this->fid = $mid;
        $this->_model = $model;
        return $this;
    }

    public function getDbModel(): ?Model
    {
        if (method_exists($this->fkey, 'find')) {
            $this->_model = $this->fkey::find($this->fid);
        }
        return $this->_model;
    }

    /**
     * method tries to return an object from the fkey, fid values
     * if no fkey is used the calling classname is used
     */
    public static function findDbModel(string $fkey, int $fid): ?Model
    {
        if (!class_exists($fkey)) {
            throw new \Tk\Exception("Invalid model class");
        }
        if (method_exists($fkey, 'find')) {
            return $fkey::find($fid);
        }
        return null;
    }

    protected static function getDbModelId(Model $model): int
    {
        $map = $model->getDataMap();
        $priKey = $map->getPrimaryKey()?->getProperty();
        return intval($model->$priKey ?? 0);
    }

}