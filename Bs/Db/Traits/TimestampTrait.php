<?php
namespace Bs\Db\Traits;



use DateTime;

/**
 * @author Michael Mifsud <http://www.tropotek.com/>
 * @link http://www.tropotek.com/
 * @license Copyright 2019 Michael Mifsud
 */
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
     * @param null|string $format   If supplied then a string of the formatted date is returned
     * @return DateTime|string
     */
    public function getModified($format = null)
    {
        if ($format && $this->modified)
            return $this->modified->format($format);
        return $this->modified;
    }

    /**
     * @param DateTime $modified
     * @return $this
     */
    public function setModified(DateTime $modified)
    {
        $this->modified = $modified;
        return $this;
    }

}