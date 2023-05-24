<?php
namespace Bs\Db\Traits;

use DateTime;

trait CreatedTrait
{
    /**
     * TimestampTrait constructor
     * Call this in parent object constructor
     */
    protected function _CreatedTrait(): void
    {
        try {
            $this->created = new \DateTime();
        } catch (\Exception $e) {}
    }

    public function getCreated(string $format = ''): DateTime|string
    {
        if ($format && $this->created)
            return $this->created->format($format);
        return $this->created;
    }

    public function setCreated(DateTime $created): static
    {
        $this->created = $created;
        return $this;
    }

}