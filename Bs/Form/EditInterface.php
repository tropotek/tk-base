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
        }
        parent::__construct($formId);
        $this->model = $model;
        $this->init();
        $this->initFormRenderer();
    }

    /**
     * init all your form fields here
     */
    abstract public function init(): void;

//    public function execute(array $values = []): void
//    {
//        parent::execute($values);
//    }

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

}