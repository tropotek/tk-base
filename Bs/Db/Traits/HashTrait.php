<?php
namespace Bs\Db\Traits;


/**
 * @author Michael Mifsud <http://www.tropotek.com/>
 * @link http://www.tropotek.com/
 * @license Copyright 2019 Michael Mifsud
 */
trait HashTrait
{
    /**
     * TODO: see if this works as expected. This may get knocked out if there is an
     *      overriding save() method in the object itself. Then add the call to getHash() in there manually
     */
    public function save()
    {
        $this->getHash();
        parent::save();
    }

    /**
     * Call this in parent object constructor if required
     */
    protected function _HashTrait()
    {
        $this->getHash();
    }


    /**
     * Get the user hash or generate one if needed
     *
     * @return string
     */
    public function getHash(): string
    {
        if (!$this->hash) {
            $this->setHash($this->generateHash());
        }
        return $this->hash;
    }

    /**
     * @param $hash
     * @return $this
     */
    protected function setHash($hash)
    {
        $this->hash = $hash;
        return $this;
    }

    /**
     * Helper method to generate an object hash
     *
     * @return string
     */
    public function generateHash()
    {
        $key = sprintf('%s%s', $this->getVolatileId(), get_class($this));
        return \Bs\Config::getInstance()->hash($key);
    }

}