<?php
namespace Bs\Db\Traits;

use DateTime;

/**
 * @author Michael Mifsud <http://www.tropotek.com/>
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
     * @param null|string $format   If supplied then a string of the formatted date is returned
     * @return DateTime|string
     */
    public function getCreated($format = null)
    {
        if ($format && $this->created)
            return $this->created->format($format);
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