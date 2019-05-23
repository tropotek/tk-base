<?php
namespace Bs\Db\Traits;



use DateTime;
use Exception;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2019 Michael Mifsud
 */
trait TimestampTrait
{
    use CreatedTrait;

    /**
     * TimestampTrait constructor
     * Call this in parent object constructor
     *
     * @throws Exception
     */
    protected function _TimestampTrait()
    {
        $this->modified = new \DateTime();
        $this->_CreatedTrait();
    }

    /**
     * @return DateTime
     */
    public function getModified(): DateTime
    {
        return $this->modified;
    }

    /**
     * @param DateTime $modified
     * @return TimestampTrait
     */
    public function setModified(DateTime $modified)
    {
        $this->modified = $modified;
        return $this;
    }

}