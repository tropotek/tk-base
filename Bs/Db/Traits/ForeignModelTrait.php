<?php
namespace Bs\Db\Traits;

use Tk\Db\Mapper\ModelInterface;

trait ForeignModelTrait
{
    use ForeignKeyTrait;

    private ?ModelInterface $_model = null;


    public function getFid(): int
    {
        return $this->fid;
    }

    public function setFid(int $fid): static
    {
        $this->fid = $fid;
        return $this;
    }

    /**
     * Alias to setForeignModel();
     */
    public function setModel(ModelInterface $model): static
    {
        return $this->setForeignModel($model);
    }

    /**
     * Alias to getForeignModel();
     */
    public function getModel(): ?ModelInterface
    {
        return $this->getForeignModel();
    }

    public function setForeignModel(ModelInterface $model): static
    {
        $this->setFkey(get_class($model));
        $this->setFid($model->getVolatileId());
        $this->_model = $model;
        return $this;
    }

    public function getForeignModel(): ?ModelInterface
    {
        if (!$this->_model && class_exists($this->getFkey().'Map')) {
            $this->_model = $this->getForeignModelMapper()->find($this->getFid());
        }
        return $this->_model;
    }

    public function validateModelId(array $errors = []): array
    {
        $errors = $this->validateFkey($errors);
//        if ($this->getFid()) {
//            $errors['fid'] = 'Invalid value: fid';
//        }
        return $errors;
    }

}