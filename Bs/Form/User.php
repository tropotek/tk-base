<?php
namespace Bs\Form;

use Tk\Form\Field;
use Tk\Form\Event;
use Tk\Form;

/**
 * Example:
 * <code>
 *   $form = new User::create();
 *   $form->setModel($obj);
 *   $formTemplate = $form->getRenderer()->show();
 *   $template->appendTemplate('form', $formTemplate);
 * </code>
 * 
 * @author Mick Mifsud
 * @created 2018-11-19
 * @link http://tropotek.com.au/
 * @license Copyright 2018 Tropotek
 */
class User extends \Bs\FormIface
{
    /**
     * Setup the controller to work with users of this role
     * @var string
     */
    protected $targetRole = '';

    /**
     * @throws \Exception
     */
    public function init()
    {

        $tab = 'Details';
        if ($this->getUser()->getId() != 1 && $this->getConfig()->getUser()->isAdmin()) {
            $list = \Bs\Db\RoleMap::create()->findFiltered(array());
            $this->appendField(new Field\Select('roleId', $list))->prependOption('-- Select --', '')->setTabGroup($tab)->setRequired(true);
        } else {
            $this->appendField(new Field\Html('roleId', $this->getUser()->getRole()->getName()))->setTabGroup($tab);
        }

        $this->appendField(new Field\Input('name'))->setTabGroup($tab)->setRequired(true);
        if ($this->getUser()->getId() != 1 && $this->getConfig()->getUser()->isAdmin()) {
            $this->appendField(new Field\Input('username'))->setTabGroup($tab)->setRequired(true);
        } else {
            $this->appendField(new Field\Html('username'))->setTabGroup($tab);
        }
        $this->appendField(new Field\Input('email'))->setTabGroup($tab)->setRequired(true);
        $this->appendField(new Field\Input('phone'))->setTabGroup($tab);
        $this->appendField(new Field\Checkbox('active'))->setTabGroup($tab);

        $tab = 'Password';
        $this->setAttr('autocomplete', 'off');
        $f = $this->appendField(new Field\Password('newPassword'))->setAttr('placeholder', 'Click to edit')
            ->setAttr('readonly')->setAttr('onfocus', "this.removeAttribute('readonly');this.removeAttribute('placeholder');")
            ->setTabGroup($tab);
        if (!$this->getUser()->getId())
            $f->setRequired(true);
        $f = $this->appendField(new Field\Password('confPassword'))->setAttr('placeholder', 'Click to edit')
            ->setAttr('readonly')->setAttr('onfocus', "this.removeAttribute('readonly');this.removeAttribute('placeholder');")
            ->setNotes('Change this users password.')->setTabGroup($tab);
        if (!$this->getUser()->getId())
            $f->setRequired(true);

        if ($this->getUser()->getId() == $this->getConfig()->getUser()->getId()) {
            $this->remove('active');
        }

        $this->appendField(new Event\Submit('update', array($this, 'doSubmit')));
        $this->appendField(new Event\Submit('save', array($this, 'doSubmit')));
        $this->appendField(new Event\Link('cancel', $this->getBackUrl()));

    }

    /**
     * @param \Tk\Request $request
     * @throws \Exception
     */
    public function execute($request = null)
    {
        $this->load($this->getConfig()->getUserMapper()->unmapForm($this->getUser()));
        parent::execute($request);
    }

    /**
     * @param Form $form
     * @param Event\Iface $event
     * @throws \Exception
     */
    public function doSubmit($form, $event)
    {
        // Load the object with form data
        $this->getConfig()->getUserMapper()->mapForm($form->getValues(), $this->getUser());

        // Password validation needs to be here
        if ($form->getFieldValue('newPassword')) {
            if ($form->getFieldValue('newPassword') != $form->getFieldValue('confPassword')) {
                $form->addFieldError('newPassword', 'Passwords do not match.');
                $form->addFieldError('confPassword');
            }
        } else {
            if (!$this->getUser()->getId()) {
                $form->addFieldError('newPassword', 'Please enter a password for this new user.');
            }
        }


        // Just a small check to ensure the user down not change their own role
        if ($this->getUser()->getId() == $this->getConfig()->getUser()->getId() && $this->getUser()->getRoleType() != $this->getConfig()->getUser()->getRoleType()) {
            $form->addError('You cannot change your own role information.');
        }
        if ($this->getUser()->getId() == $this->getConfig()->getUser()->getId() && !$this->getUser()->isActive()) {
            $form->addError('You cannot change your own active status.');
        }

        $form->addFieldErrors($this->getUser()->validate());
        if ($form->hasErrors()) {
            return;
        }

        if ($form->getFieldValue('newPassword')) {
            $this->getUser()->setNewPassword($form->getFieldValue('newPassword'));
        }

        // Keep the admin account available and working. (hack for basic sites)
        if ($this->getUser()->getId() == 1) {
            $this->getUser()->active = true;
            $this->getUser()->username = 'admin';
            $this->getUser()->roleId = \Bs\Db\Role::DEFAULT_TYPE_ADMIN;
        }

        $this->getUser()->save();


        \Tk\Alert::addSuccess('Record saved!');
        $event->setRedirect($this->getBackUrl());
        if ($form->getTriggeredEvent()->getName() == 'save') {
            $event->setRedirect(\Tk\Uri::create()->set('userId', $this->getUser()->getId()));
        }
    }

    /**
     * @return \Tk\Db\ModelInterface|\Bs\Db\User
     */
    public function getUser()
    {
        return $this->getModel();
    }

    /**
     * @param \Bs\Db\User $user
     * @return $this
     */
    public function setUser($user)
    {
        return $this->setModel($user);
    }

    /**
     * @return string
     */
    public function getTargetRole()
    {
        return $this->targetRole;
    }

    /**
     * @param string $targetRole
     * @return User
     */
    public function setTargetRole($targetRole)
    {
        $this->targetRole = $targetRole;
        return $this;
    }
    
}