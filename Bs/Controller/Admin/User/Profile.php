<?php
namespace Bs\Controller\Admin\User;

use Tk\Request;
use Dom\Template;
use Tk\Form;
use Tk\Form\Field;
use Tk\Form\Event;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Profile extends \Bs\Controller\AdminEditIface
{

    /**
     * @var \Bs\Db\User|\Bs\Db\UserIface
     */
    protected $user = null;


    /**
     * @throws \Exception
     */
    public function __construct()
    {
        $this->setPageTitle('My Profile');
        //$this->getCrumbs()->reset();
    }

    /**
     * @return \Bs\Db\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param \Tk\Request $request
     * @throws \Exception
     */
    public function init($request)
    {
        $this->user = $this->getConfig()->getAuthUser();
    }

    /**
     * @param \Tk\Request $request
     * @throws \Exception
     */
    public function doDefault(\Tk\Request $request)
    {
        $this->init($request);

        $this->setForm(\Bs\Form\User::create()->setModel($this->user));

        if ($this->getForm()->getField('active'))
            $this->getForm()->removeField('active');
        if ($this->getForm()->getField('username'))
            $this->getForm()->getField('username')->setAttr('disabled')->addCss('form-control disabled')->removeCss('tk-input-lock');
        if ($this->getForm()->getField('uid'))
            $this->getForm()->getField('uid')->setAttr('disabled')->addCss('form-control disabled')->removeCss('tk-input-lock');
        if ($this->getForm()->getField('email'))
            $this->getForm()->getField('email')->setAttr('disabled')->addCss('form-control disabled')->removeCss('tk-input-lock');

        $this->getForm()->execute();
    }

    /**
     * @return \Dom\Template
     * @throws \Exception
     */
    public function show()
    {
        $template = parent::show();

        // Render the form
        $template->appendTemplate('panel', $this->form->show());
        if ($this->user->getId())
            $template->setAttr('panel', 'data-panel-title', $this->user->getName() . ' - [ID ' . $this->user->getId() . ']');

        return $template;
    }


    /**
     * DomTemplate magic method
     *
     * @return Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<HTML
<div class="tk-panel" data-panel-icon="fa fa-user" var="panel"></div>
HTML;

        return \Dom\Loader::load($xhtml);
    }

}
