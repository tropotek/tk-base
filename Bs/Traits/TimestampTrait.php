<?php
namespace Bs\Traits;

use DateTime;

trait TimestampTrait
{
    use CreatedTrait;

    /**
     * TimestampTrait constructor
     * Call this in parent object constructor
     */
    protected function _TimestampTrait(): void
    {
        $this->modified = new \DateTime();
        $this->_CreatedTrait();
    }

    /**
     * @param string $format   If supplied then a string of the formatted date is returned
     */
    public function getModified(string $format = ''): string|DateTime
    {
        if (!empty($format)) {
            return $this->modified->format($format);
        }
        return $this->modified;
    }

    public function setModified(DateTime $modified): static
    {
        $this->modified = $modified;
        return $this;
    }

}