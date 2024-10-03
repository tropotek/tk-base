<?php
namespace Bs\Traits;

trait OrderByTrait
{

    /**
     * @return int
     */
    public function getOrderBy(): int
    {
        return $this->orderBy;
    }

    /**
     * @param int $orderBy
     * @return $this
     */
    public function setOrderBy($orderBy): static
    {
        $this->orderBy = $orderBy;
        return $this;
    }

    // TODO: add any helper methods that may be needed.



}