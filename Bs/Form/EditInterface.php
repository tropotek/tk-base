<?php

namespace Bs\Form;

use Dom\Renderer\DisplayInterface;
use Dom\Template;
use Tk\Db\Mapper\Model;
use Tk\Db\Mapper\ModelInterface;
use Tk\Form;
use Tk\FormRenderer;

/**
 * Use this interface to create edit Forms using a model object
 */
abstract class EditInterface extends Form implements DisplayInterface
{

    protected FormRenderer $formRenderer;

    protected null|array|Model $model;


    public function __construct(null|array|Model $model = null, string $formId = '')
    {
        if (!$formId) {
            $formId = \Tk\ObjectUtil::basename(get_class($this));
            $formId = strtolower(preg_replace('/[A-Z]/', '_$0', $formId));
            $formId = trim($formId, '_');
        }
        parent::__construct($formId);
        $this->setModel($model);
    }

    public function init(): static
    {
        $this->initFields();
        $this->initFormRenderer();

        return $this;
    }

    /**
     * Add form fields, events here
     */
    abstract protected function initFields(): void;

    public function execute(array $values = []): static
    {
        parent::execute($values);

        return $this;
    }

    public function show(): ?Template
    {
        return $this->getFormRenderer()->show();
    }


    protected function initFormRenderer(): static
    {
        $this->formRenderer = new FormRenderer($this->getForm());
        return $this;
    }

    public function getFormRenderer(): FormRenderer
    {
        return $this->formRenderer;
    }

    /**
     * get the Array or Model that is being edited
     */
    public function getModel(): null|array|Model|ModelInterface
    {
        return $this->model;
    }

    public function setModel(Model|array|null $model): EditInterface
    {
        $this->model = $model;
        return $this;
    }

    public function getBackUrl(): \Tk\Uri
    {
        return $this->getFactory()->getBackUrl();
    }

}