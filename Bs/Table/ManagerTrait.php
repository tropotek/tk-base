<?php

namespace Bs\Table;

/**
 * @deprecated To be removed
 */
trait ManagerTrait
{
    protected ?ManagerInterface $table = null;


    public function getTable(): ?ManagerInterface
    {
        return $this->table;
    }

    protected function setTable(ManagerInterface $table): static
    {
        $this->table = $table;
        return $this;
    }

}