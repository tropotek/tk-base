<?php
namespace Bs\Db\Traits;

use DateTime;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2019 Michael Mifsud
 */
trait CreatedTrait
{


    /**
     * TimestampTrait constructor
     * Call this in parent object constructor
     */
    protected function _CreatedTrait()
    {
        try {
            $this->created = new \DateTime();
        } catch (\Exception $e) {}
    }

    /**
     * @return DateTime
     */
    public function getCreated(): DateTime
    {
        return $this->created;
    }

    /**
     * @param DateTime $created
     * @return $this
     */
    public function setCreated(DateTime $created)
    {
        $this->created = $created;
        return $this;
    }

}