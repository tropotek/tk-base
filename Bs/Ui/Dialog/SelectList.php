<?php

namespace Bs\Ui\Dialog;

use Bs\Ui\Dialog;
use Dom\Template;
use Tk\Ui\Traits\AttributesTrait;

/**
 * Create A selectable list of options within a dialog.
 *
 */
class SelectList extends Dialog
{
    use AttributesTrait;

    protected array  $rows         = [];
    protected bool   $showCheckbox = false;
    protected bool   $showSearch   = false;
    protected bool   $multiple     = false;


    public function __construct(string $title, array $rows)
    {
        parent::__construct($title);
        $this->rows = $rows;

    }

//    public function init(): void
//    {
//        $this->getOnInit()->execute($this);
//    }

//    public function execute(): void
//    {
//        $this->getOnExecute()->execute($this);
//    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();

        // Add content to dialog

        return parent::show();
    }

    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    public function setMultiple(bool $multiple): static
    {
        $this->multiple = $multiple;
        return $this;
    }
    public function getRows(): array
    {
        return $this->rows;
    }

    public function isShowCheckbox(): bool
    {
        return $this->showCheckbox;
    }

    public function setShowCheckbox(bool $showCheckbox): static
    {
        $this->showCheckbox = $showCheckbox;
        return $this;
    }

    public function isShowSearch(): bool
    {
        return $this->showSearch;
    }

    public function setShowSearch(bool $showSearch): static
    {
        $this->showSearch = $showSearch;
        return $this;
    }

}