<?php
namespace Bs\Controller\Admin\User;

use Tk\Request;
use Dom\Template;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Edit extends \Bs\Controller\AdminEditIface
{
    /**
     * Setup the controller to work with users of this role
     * @var string
     */
    protected $targetRole = 'user';

    /**
     * @var \Bs\Db\User
     */
    protected $user = null;


    /**
     * @throws \Exception
     */
    public function __construct()
    {
        $this->setPageTitle('User Edit');
    }

    /**
     * @param \Tk\Request $request
     * @param string $targetRole
     * @throws \Exception
     */
    public function doDefaultRole(\Tk\Request $request, $targetRole)
    {
        $this->targetRole = $targetRole;
        switch($targetRole) {
            case \Bs\Db\Role::TYPE_ADMIN:
                $this->setPageTitle('Admin Edit');
                break;
        }
        $this->doDefault($request);
    }

    /**
     * @param \Tk\Request $request
     * @throws \Exception
     */
    public function doDefault(\Tk\Request $request)
    {
        $this->init($request);

        $this->setForm(\Bs\Form\User::create()->setModel($this->user));

        $this->getForm()->execute();

//        $this->buildForm();
//        $this->execute();
    }

    /**
     * @param Request $request
     * @throws \Exception
     */
    public function init($request)
    {
        $this->user = $this->getConfig()->createUser();
        $this->user->roleId = \Bs\Db\Role::DEFAULT_TYPE_USER;
        if ($request->get('userId')) {
            $this->user = $this->getConfig()->getUserMapper()->find($request->get('userId'));
        }
    }

//    public function buildForm()
//    {
//        $this->form = $this->getConfig()->createForm('user-edit');
//        $this->form->setRenderer($this->getConfig()->createFormRenderer($this->form));
//
//        $tab = 'Details';
//        if ($this->user->getId() != 1 && $this->getUser()->isAdmin()) {
//            $list = \Bs\Db\RoleMap::create()->findFiltered(array());
//            $this->form->appendField(new Field\Select('roleId', $list))->prependOption('-- Select --', '')->setTabGroup($tab)->setRequired(true);
//        } else {
//            $this->form->appendField(new Field\Html('roleId', $this->user->getRole()->getName()))->setTabGroup($tab);
//        }
//
//        $this->form->appendField(new Field\Input('name'))->setTabGroup($tab)->setRequired(true);
//        if ($this->user->getId() != 1 && $this->getUser()->isAdmin()) {
//            $this->form->appendField(new Field\Input('username'))->setTabGroup($tab)->setRequired(true);
//        } else {
//            $this->form->appendField(new Field\Html('username'))->setTabGroup($tab);
//        }
//        $this->form->appendField(new Field\Input('email'))->setTabGroup($tab)->setRequired(true);
//        if ($this->user->getId() != 1)
//            $this->form->appendField(new Field\Checkbox('active'))->setTabGroup($tab);
//
//        $tab = 'Password';
//        $this->form->setAttr('autocomplete', 'off');
//        $f = $this->form->appendField(new Field\Password('newPassword'))->setAttr('placeholder', 'Click to edit')
//            ->setAttr('readonly')->setAttr('onfocus', "this.removeAttribute('readonly');this.removeAttribute('placeholder');")
//            ->setTabGroup($tab);
//        if (!$this->user->getId())
//            $f->setRequired(true);
//        $f = $this->form->appendField(new Field\Password('confPassword'))->setAttr('placeholder', 'Click to edit')
//            ->setAttr('readonly')->setAttr('onfocus', "this.removeAttribute('readonly');this.removeAttribute('placeholder');")
//            ->setNotes('Change this users password.')->setTabGroup($tab);
//        if (!$this->user->getId())
//            $f->setRequired(true);
//
//        $this->form->appendField(new Event\Submit('update', array($this, 'doSubmit')));
//        $this->form->appendField(new Event\Submit('save', array($this, 'doSubmit')));
//        $this->form->appendField(new Event\Link('cancel', $this->getBackUrl()));
//    }

//    public function execute()
//    {
//        $this->form->load($this->getConfig()->getUserMapper()->unmapForm($this->user));
//        $this->form->execute();
//
//    }

//    public function doSubmit($form, $event)
//    {
//        // Load the object with data from the form using a helper object
//        $this->getConfig()->getUserMapper()->mapForm($form->getValues(), $this->user);
//
//        // Password validation needs to be here
//        if ($this->form->getFieldValue('newPassword')) {
//            if ($this->form->getFieldValue('newPassword') != $this->form->getFieldValue('confPassword')) {
//                $form->addFieldError('newPassword', 'Passwords do not match.');
//                $form->addFieldError('confPassword');
//            }
//        }
//        $form->addFieldErrors($this->user->validate());
//
//        // Just a small check to ensure the user down not change their own role
//        if ($this->user->getId() == $this->getUser()->getId() && $this->user->getRoleType() != $this->getUser()->getRoleType()) {
//            $form->addError('You cannot change your own role information.');
//        }
//        if ($this->user->getId() == $this->getUser()->getId() && !$this->user->isActive()) {
//            $form->addError('You cannot change your own active status.');
//        }
//
//        if ($form->hasErrors()) {
//            return;
//        }
//
//        if ($this->form->getFieldValue('newPassword')) {
//            $this->user->setNewPassword($this->form->getFieldValue('newPassword'));
//        }
//
//        // Keep the admin account available and working. (hack for basic sites)
//        if ($this->user->getId() == 1) {
//            $this->user->active = true;
//            $this->user->username = 'admin';
//            $this->user->roleId = \Bs\Db\Role::DEFAULT_TYPE_ADMIN;
//        }
//
//        $this->user->save();
//
//        \Tk\Alert::addSuccess('Record saved!');
//        $event->setRedirect(\Tk\Uri::create());
//        if ($form->getTriggeredEvent()->getName() == 'update') {
//            $event->setRedirect($this->getBackUrl());
//        }
//    }

    /**
     * @return \Dom\Template
     * @throws \Exception
     */
    public function show()
    {
        if ($this->user->getId() && $this->getConfig()->getMasqueradeHandler()->canMasqueradeAs($this->getUser(), $this->user)) {
            $this->getActionPanel()->add(\Tk\Ui\Button::create('Masquerade',
                \Bs\Uri::create()->reset()->set(\Bs\Listener\MasqueradeHandler::MSQ, $this->user->getHash()), 'fa fa-user-secret'))
                ->setAttr('data-confirm', 'You are about to masquerade as the selected user?')->addCss('tk-masquerade');
        }

        $template = parent::show();
        
        // Render the form
        $template->appendTemplate('form', $this->form->show());
        
        if ($this->user->id)
            $template->setAttr('form', 'data-panel-title', $this->user->name . ' - [ID ' . $this->user->id . ']');
        
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
<div>

  <div class="tk-panel" data-panel-icon="fa fa-user" var="form"></div>
    
</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }

}