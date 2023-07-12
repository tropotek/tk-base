<?php

namespace Bs\Form;


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