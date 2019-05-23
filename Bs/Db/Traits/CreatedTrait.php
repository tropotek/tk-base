<?php
namespace Bs\Db\Traits;

use DateTime;
use Exception;

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
     *
     * @throws Exception
     */
    protected function _CreatedTrait()
    {
        $this->created = new \DateTime();
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
     * @return TimestampTrait
     */
    public function setCreated(DateTime $created)
    {
        $this->created = $created;
        return $this;
    }

}