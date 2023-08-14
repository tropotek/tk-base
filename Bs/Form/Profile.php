<?php
namespace Bs\Form;

use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Alert;
use Tk\Db\Mapper\Model;
use Tk\Form;
use Tk\FormRenderer;
use Tk\Form\Field\Input;
use Tk\Form\Field\Checkbox;
use Tk\Form\Field\Hidden;
use Tk\Traits\SystemTrait;
use Tk\Uri;

class Profile extends EditInterface
{

    protected function initFields(): void
    {
        $tab = 'Details';
        $this->getForm()->appendField(new Hidden('userId'))->setGroup($tab);

        $list = \Bs\Db\User::getTitleList();
        $this->getForm()->appendField(new Form\Field\Select('nameTitle', $list))
            ->setGroup($tab)
            ->setLabel('Title')
            ->prependOption('', '');

        $this->getForm()->appendField(new Input('nameFirst'))
            ->setGroup($tab)
            ->setLabel('First Name')
            ->setRequired();

        $this->getForm()->appendField(new Input('nameLast'))
            ->setGroup($tab)
            ->setLabel('Last Name');

//        $this->getForm()->appendField(new Input('nameDisplay'))
//            ->setGroup($group)
//            ->setLabel('Preferred Name');

        $this->getForm()->appendField(new Input('username'))->setGroup($tab)
            ->setDisabled()
            ->setReadonly();

        $this->getForm()->appendField(new Input('email'))->setGroup($tab)
            ->addCss('tk-input-lock')
            ->setRequired();

        if ($this->getUser()->isType(\Bs\Db\User::TYPE_STAFF)) {
            $list = array_flip($this->getUser()->getAvailablePermissions());
            $this->getForm()->appendField(new Checkbox('perm', $list))
                ->setGroup('Permissions')
                ->setDisabled()
                ->setReadonly();
        }

        if ($this->getConfig()->get('user.profile.password')) {
            $tab = 'Password';
            $this->getForm()->appendField(new Form\Field\Password('currentPass'))->setGroup($tab)
                ->setLabel('Current Password')
                ->setAttr('autocomplete', 'new-password');
            $this->getForm()->appendField(new Form\Field\Password('newPass'))->setGroup($tab)
                ->setLabel('New Password')
                ->setAttr('autocomplete', 'new-password');
            $this->getForm()->appendField(new Form\Field\Password('confPass'))->setGroup($tab)
                ->setLabel('Confirm Password')
                ->setAttr('autocomplete', 'new-password');
        }

        //$this->getForm()->appendField(new Checkbox('active', ['Enable User Login' => 'active']))->setDisabled();
        //$this->getForm()->appendField(new Form\Field\Textarea('notes'))->setGroup($group);

        $this->getForm()->appendField(new Form\Action\SubmitExit('save', [$this, 'onSubmit']));
        $this->getForm()->appendField(new Form\Action\Link('cancel', $this->getFactory()->getBackUrl()));

    }

    public function execute(array $values = []): static
    {
        $load = $this->getUser()->getMapper()->getFormMap()->getArray($this->getUser());
        $load['userId'] = $this->getUser()->getUserId();
        $load['perm'] = $this->getUser()->getPermissionList();
        $this->getForm()->setFieldValues($load); // Use form data mapper if loading objects

        parent::execute($values);
        return $this;
    }

    public function onSubmit(Form $form, Form\Action\ActionInterface $action): void
    {
        $this->getUser()->getMapper()->getFormMap()->loadObject($this->getUser(), $form->getFieldValues());

        if ($form->getField('currentPass') && $form->getFieldValue('currentPass')) {
            if (!password_verify($form->getFieldValue('currentPass'), $this->getUser()->getPassword())) {
                $form->addFieldError('currentPass', 'Invalid current password, password not updated');
            }
            if ($form->getField('newPass') && $form->getFieldValue('newPass')) {
                if ($form->getFieldValue('newPass') != $form->getFieldValue('confPass')) {
                    $form->addFieldError('newPass', 'Passwords do not match');
                } else {
                    if (!$e = \Bs\Db\User::validatePassword($form->getFieldValue('newPass'))) {
                        $form->addFieldError('newPass', 'Week password: ' . implode(', ', $e));
                    }
                }
            } else {
                $form->addFieldError('newPass', 'Please supply a new password');
            }
        }

        $form->addFieldErrors($this->getUser()->validate());
        if ($form->hasErrors()) {
            Alert::addError('Form contains errors.');
            return;
        }
        if ($form->getFieldValue('currentPass')) {
            $this->getUser()->setPassword(\Bs\Db\User::hashPassword($form->getFieldValue('newPass')));
            Alert::addSuccess('Your password has been updated, remember to use this on your next login.');
        }
        $this->getUser()->save();

        Alert::addSuccess('Form save successfully.');
        $action->setRedirect(Uri::create('/profile'));
        if ($form->getTriggeredAction()->isExit()) {
            $action->setRedirect(Uri::create('/'));
        }
    }

    public function show(): ?Template
    {
        $this->getForm()->getField('nameTitle')->addFieldCss('col-1');
        $this->getForm()->getField('nameFirst')->addFieldCss('col-5');
        $this->getForm()->getField('nameLast')->addFieldCss('col-6');
        //$this->getForm()->getField('nameDisplay')->addFieldCss('col-5');

        $this->getForm()->getField('username')->addFieldCss('col-6');
        $this->getForm()->getField('email')->addFieldCss('col-6');
        $renderer = $this->getFormRenderer();
        $renderer->addFieldCss('mb-3');
        return $renderer->show();
    }

    public function getUser(): \Bs\Db\User|Model
    {
        return $this->getModel();
    }
}