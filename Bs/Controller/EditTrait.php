<?php
namespace Bs\Controller;

/**
 * @author Tropotek <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2017 Tropotek
 */
trait EditTrait
{

    /**
     * @var \Tk\Form
     */
    protected $form = null;

    /**
     * @return \Tk\Form
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * @param \Tk\Form $form
     * @return EditTrait
     */
    protected function setForm($form)
    {
        $this->form = $form;
        return $this;
    }

}