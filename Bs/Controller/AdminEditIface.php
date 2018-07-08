<?php
namespace Bs\Controller;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class AdminEditIface extends AdminIface
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
    

}