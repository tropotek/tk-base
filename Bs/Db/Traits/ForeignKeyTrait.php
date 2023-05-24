<?php
namespace Bs\Db\Traits;

use Tk\Db\Mapper\Mapper;
use Tk\Db\Mapper\Model;

trait ForeignKeyTrait
{

    public function getFkey(): string
    {
        return $this->fkey;
    }

    public function setFkey(string $fkey): static
    {
        $this->fkey = $fkey;
        return $this;
    }

    public function getForeignModelMapper(): ?Mapper
    {
        return Model::getMapperInstance($this->getFkey().'Map');
    }

    public function findForeignModel(int $modelId): ?Model
    {
        return $this->getForeignModelMapper()?->find($modelId);
    }

    public function validateFkey(array $errors = []): array
    {
        if (!$this->getFkey()) {
            $errors['fkey'] = 'Invalid value: fkey';
        }
        return $errors;
    }

}