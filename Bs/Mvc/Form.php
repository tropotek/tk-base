<?php

namespace Bs\Mvc;

use Bs\Ui\Breadcrumbs;
use Dom\Renderer\DisplayInterface;
use Dom\Template;
use Tk\Form\Renderer\Dom\Renderer;
use Tk\Uri;
use Tk\Db\Model;

/**
 * New form and renderer to replace \Bs\Form\EditInterface
 * Facilitates creating forms for DbModel objects
 */
class Form extends \Tk\Form implements DisplayInterface
{

    const string MODE_CREATE = 'create';
    const string MODE_EDIT   = 'edit';
    const string MODE_VIEW   = 'view';  // todo: implement a view form renderer ??

    protected string   $mode   = self::MODE_CREATE;
    protected ?Model   $model  = null;
    protected Renderer $renderer;


    public function __construct(?Model $model = null, ?string $formId = null)
    {
        // generate form ID
        if (is_null($formId)) {
            $formId = static::class;
            if (($model instanceof Model)) {
                $formId = $model::class;
            }
            $formId = strval(\Tk\ObjectUtil::basename($formId));
            $formId = strtolower(preg_replace('/[A-Z]/', '-$0', $formId));
            $formId = trim($formId, '_-');
        }

        parent::__construct($formId);

        $this->form = $this;
        $this->renderer = new Renderer($this);

        $this->setModel($model);
    }

    /**
     * Add form fields and events in this method
     */
    public function init(): static { return $this; }

    public function show(): ?Template
    {
        return $this->getRenderer()->show();
    }

    /**
     * Change the main table template to
     * Call this in place of $table->show(); for htmx components
     */
    public function htmxShow(): ?Template
    {
        // setup table for hx requests
        $this->form->removeAttr('action');
        $this->form->setAttr('hx-disinherit', '*');
        if(!$this->form->hasAttr('hx-post')) {
            $this->form->setAttr('hx-post', Uri::create());
        }
        if(!$this->form->hasAttr('hx-target')) {
            $this->form->setAttr('hx-target', "#{$this->form->getId()}");
        }
        if(!$this->form->hasAttr('hx-select')) {
            $this->form->setAttr('hx-select', "#{$this->form->getId()}");
        }
        if(!$this->form->hasAttr('hx-swap')) {
            $this->form->setAttr('hx-swap', 'outerHTML');
        }

        return $this->show();
    }

    public function getRenderer(): ?Renderer
    {
        return $this->renderer;
    }

    public function getModel(): ?Model
    {
        return $this->model;
    }

    public function setModel(?Model $model): static
    {
        $this->model = $model;
        if ($this->model?->getId()) {
            $this->setMode(self::MODE_EDIT);
        }
        return $this;
    }

    /**
     * @deprecated use Breadcrumbs::getBackUrl()
     */
    public function getBackUrl(): Uri
    {
        return Breadcrumbs::getBackUrl();
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    public function setMode(string $mode): static
    {
        $this->mode = $mode;
        return $this;
    }

    public function isCreate(): bool
    {
        return $this->mode === self::MODE_CREATE;
    }

    public function isEdit(): bool
    {
        return $this->mode === self::MODE_EDIT;
    }

    public function isView(): bool
    {
        return $this->mode === self::MODE_VIEW;
    }

}