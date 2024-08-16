<?php
namespace Bs\Db\Traits;

/**
 * Expected to be attached to a \Tk\Db\Mapper\Model object
 * @deprecated
 */
trait HashTrait
{

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
        $key = sprintf('%s%s', $this->userId, get_class($this));
        return hash('md5', $key);
    }

}