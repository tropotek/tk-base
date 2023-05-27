<?php
namespace Bs\Db\Traits;

use DateTime;

trait TimestampTrait
{
    use CreatedTrait;

    /**
     * TimestampTrait constructor
     * Call this in parent object constructor
     */
    protected function _TimestampTrait()
    {
        try {
            $this->modified = new \DateTime();
            $this->_CreatedTrait();
        } catch (\Exception $e) {}
    }

    /**
     * @param string $format   If supplied then a string of the formatted date is returned
     */
    public function getModified(string $format = ''): string|DateTime
    {
        if ($format && $this->modified)
            return $this->modified->format($format);
        return $this->modified;
    }

    public function setModified(DateTime $modified): static
    {
        $this->modified = $modified;
        return $this;
    }

}