<?php

namespace Bs;

use Dom\Renderer\DisplayInterface;
use Dom\Template;
use Tk\Form\Renderer\Dom\Renderer;
use Tk\Traits\SystemTrait;
use Tk\Uri;
use Tt\DbModel;

/**
 * New form and renderer to replace \Bs\Form\EditInterface
 * Facilitates creating forms for DbModel objects
 */
class Form extends \Tk\Form implements DisplayInterface
{
    use SystemTrait;

    protected ?Renderer $renderer = null;
    protected ?DbModel  $model    = null;


    public function __construct(?DbModel $model = null)
    {
        $formId = \Tk\ObjectUtil::basename(static::class);
        $formId = strtolower(preg_replace('/[A-Z]/', '_$0', $formId));
        $formId = trim($formId, '_');
        parent::__construct($formId);

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

    public function getRenderer(): ?Renderer
    {
        return $this->renderer;
    }

    public function getModel(): ?DbModel
    {
        return $this->model;
    }

    public function setModel(?DbModel $model): static
    {
        $this->model = $model;
        return $this;
    }

    public function getBackUrl(): Uri
    {
        return Factory::instance()->getBackUrl();
    }

}