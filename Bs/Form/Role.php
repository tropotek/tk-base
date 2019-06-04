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
class Role extends \Bs\FormIface
{

    /**
     * @throws \Exception
     */
    public function init()
    {
        $tab = 'Details';
        if ($this->getRole()) {
            $this->appendField(new Field\Html('type'))->setTabGroup($tab);
        } else {
            $list = array(
                '-- Type --' => '',
                'Admin' => \Bs\Db\Role::TYPE_ADMIN
                //,'User' => \Bs\Db\Role::TYPE_User
            );
            $this->appendField(new Field\Select('type', $list))->setLabel('Role Type')->setTabGroup($tab)->setRequired();
        }
        $this->appendField(new Field\Input('name'))->setTabGroup($tab)->setRequired();
        $this->appendField(new Field\Input('description'))->setTabGroup($tab);
        $this->appendField(new Field\Checkbox('active'))->setTabGroup($tab)
            ->setNotes('Making a role inactive will result in the user having no permissions, the same permissions as the default role of its type.');

        $this->setupPermissionFields();

        $this->appendField(new Event\Submit('update', array($this, 'doSubmit')));
        $this->appendField(new Event\Submit('save', array($this, 'doSubmit')));
        $this->appendField(new Event\Link('cancel', $this->getBackUrl()));

    }

    /**
     * Override for your own apps
     *
     * @throws \Exception
     */
    protected function setupPermissionFields()
    {
        $tab = 'Permission';

//        $form->appendField(new Field\Checkbox(\Bs\Db\Permission::MANAGE_STAFF))->setLabel('Manage Staff')->setTabGroup($tab)
//            ->setNotes('Add/Edit Staff user accounts');
//        $form->appendField(new Field\Checkbox(\Bs\Db\Permission::MANAGE_STUDENT))->setLabel('Manage Students')->setTabGroup($tab)
//            ->setNotes('Add/Edit Student user accounts');
//        $form->appendField(new Field\Checkbox(\Bs\Db\Permission::MANAGE_SUBJECT))->setLabel('Manage Subjects')->setTabGroup($tab)
//            ->setNotes('Add/Edit subject and student enrollments');
    }

    /**
     * @param \Tk\Request $request
     * @throws \Exception
     */
    public function execute($request = null)
    {
        $this->load(array_combine($this->getRole()->getPermissions(), $this->getRole()->getPermissions()));
        $this->load($this->getConfig()->getRoleMapper()->unmapForm($this->getRole()));
        parent::execute($request);
    }

    /**
     * @param Form $form
     * @param Event\Iface $event
     * @throws \Exception
     */
    public function doSubmit($form, $event)
    {
        // Load the object with data from the form using a helper object
        $this->getConfig()->getRoleMapper()->mapForm($form->getValues(), $this->getRole());

        $form->addFieldErrors($this->getRole()->validate());

        if ($form->hasErrors()) {
            return;
        }

        $this->getRole()->save();

        if ($this->getRole()->isStatic()) {
            \Tk\Alert::addWarning('You are trying to edit a static ROLE. These roles are set by the system and cannot be modified.');
        } else {
            // Update the required permissions
//            if ($this->getConfig()->getInstitutionId()) {
//                $this->getConfig()->getRoleMapper()->addInstitution($this->role->getVolatileId(), $this->getConfig()->getInstitutionId());
//            }

            // Save submitted permissions
            $this->getRole()->removePermission();
            foreach ($form->getValues('/^perm\./') as $name) {
                $this->getRole()->addPermission($name);
            }

            \Tk\Alert::addSuccess('Record saved!');
        }
        $event->setRedirect($this->getConfig()->getBackUrl());
        if ($form->getTriggeredEvent()->getName() == 'save') {
            $event->setRedirect(\Tk\Uri::create()->reset()->set('roleId', $this->getRole()->getId()));
        }
    }

    /**
     * @return \Tk\Db\ModelInterface|\Bs\Db\Role
     */
    public function getRole()
    {
        return $this->getModel();
    }

    /**
     * @param \Bs\Db\Role $obj
     * @return $this
     */
    public function setRole($obj)
    {
        return $this->setModel($obj);
    }

    
}