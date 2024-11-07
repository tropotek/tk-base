<?php
namespace Bs\Traits;


trait TimestampTrait
{
    use CreatedTrait;

    /**
     * @param string $format If supplied then a string of the formatted date is returned
     */
    public function getModified(string $format = ''): string|\DateTimeInterface
    {
        if (!empty($format)) {
            return $this->modified->format($format);
        }
        return $this->modified;
    }

    public function setModified(\DateTimeInterface $modified): static
    {
        $this->modified = $modified;
        return $this;
    }

}