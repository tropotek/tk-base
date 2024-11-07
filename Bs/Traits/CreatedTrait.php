<?php
namespace Bs\Traits;

trait CreatedTrait
{

    public function getCreated(string $format = ''): string|\DateTimeInterface
    {
        if (!empty($format)) {
            return $this->created->format($format);
        }
        return $this->created;
    }

    public function setCreated(\DateTimeInterface $created): static
    {
        $this->created = $created;
        return $this;
    }

}