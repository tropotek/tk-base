<?php
namespace Bs\Db\Traits;

/**
 * Expected to be attached to a \Tk\Db\Mapper\Model object
 */
trait HashTrait
{

    /**
     * TODO: see if this works as expected. This may get knocked out if there is an
     *      overriding save() method in the object itself. Then add the call to getHash() in there manually
     *      ------------
     *      Refactor all this hash initialization for the new DB and mappers
     *
     */
    public function save(): void
    {
        $this->getHash();
        parent::save();
    }

    /**
     * Get the user hash or generate one if needed
     */
    public function getHash(): string
    {
        if (!$this->hash) {
            $this->setHash($this->generateHash());
        }
        return $this->hash;
    }

    protected function setHash(string $hash): static
    {
        $this->hash = $hash;
        return $this;
    }

    /**
     * Helper method to generate an object hash
     */
    public function generateHash(): string
    {
        $key = sprintf('%s%s', $this->getVolatileId(), get_class($this));
        return hash('md5', $key);
    }

}