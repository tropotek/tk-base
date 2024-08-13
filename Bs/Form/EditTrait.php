<?php

namespace Bs\Form;

/**
 * @deprecated To be removed
 */
trait EditTrait
{
    protected ?EditInterface $form = null;


    public function getForm(): ?EditInterface
    {
        return $this->form;
    }

    protected function setForm(EditInterface $form): static
    {
        $this->form = $form;
        return $this;
    }

}