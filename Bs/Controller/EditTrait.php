<?php
namespace Bs\Controller;

/**
 * @author Tropotek <http://www.tropotek.com/>
 * @link http://www.tropotek.com/
 * @license Copyright 2017 Tropotek
 */
trait EditTrait
{

    /**
     * @var \Tk\Form|\Bs\FormIface
     */
    protected $form = null;

    /**
     * @return \Tk\Form|\Bs\FormIface
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * @param \Tk\Form|\Bs\FormIface $form
     * @return EditTrait
     */
    protected function setForm($form)
    {
        $this->form = $form;
        return $this;
    }


    /**
     * Use this to init the form before execute is called
     * @param \Tk\Request $request
     */
    public function initForm(\Tk\Request $request) { }
}