<?php
namespace Bs\Traits;

use DateTime;

trait CreatedTrait
{
    /**
     * TimestampTrait constructor
     * Call this in parent object constructor
     */
    protected function _CreatedTrait(): void
    {
        $this->created = new \DateTime();
    }

    public function getCreated(string $format = ''): DateTime|string
    {
        if (!empty($format)) {
            return $this->created->format($format);
        }
        return $this->created;
    }

    public function setCreated(DateTime $created): static
    {
        $this->created = $created;
        return $this;
    }

}