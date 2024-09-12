<?php
namespace Bs\Form;

use Bs\Factory;
use Bs\Form;
use Dom\Template;
use Tk\Alert;
use Tk\Config;
use Tk\Form\Action\Link;
use Tk\Form\Action\SubmitExit;
use Tk\Form\Field\Checkbox;
use Tk\Form\Field\Hidden;
use Tk\Form\Field\Input;
use Tk\Form\Field\Password;
use Tk\Form\Field\Select;
use Tk\Uri;

class Profile extends Form
{

    public function init(): static
    {
        $tab = 'Details';
        $this->getForm()->appendField(new Hidden('userId'))->setGroup($tab);

        $list = \Bs\Db\User::getTitleList();
        $this->getForm()->appendField(new Select('nameTitle', $list))
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

        $this->getForm()->appendField(new Input('username'))->setGroup($tab)
            ->setDisabled()
            ->setReadonly();

        $this->getForm()->appendField(new Input('email'))->setGroup($tab)
            ->addCss('tk-input-lock')
            ->setRequired();

        if ($this->getUser()->isType(\Bs\Db\User::TYPE_STAFF)) {
            $list = array_flip(Factory::instance()->getAvailablePermissions($this->getUser()));
            $this->getForm()->appendField(new Checkbox('perm', $list))
                ->setGroup('Permissions')
                ->setDisabled()
                ->setReadonly();
        }

        if (Config::instance()->get('user.profile.password')) {
            $tab = 'Password';
            $this->getForm()->appendField(new Password('currentPass'))->setGroup($tab)
                ->setLabel('Current Password')
                ->setAttr('autocomplete', 'new-password');
            $this->getForm()->appendField(new Password('newPass'))->setGroup($tab)
                ->setLabel('New Password')
                ->setAttr('autocomplete', 'new-password');
            $this->getForm()->appendField(new Password('confPass'))->setGroup($tab)
                ->setLabel('Confirm Password')
                ->setAttr('autocomplete', 'new-password');
        }

        $this->getForm()->appendField(new SubmitExit('save', [$this, 'onSubmit']));
        $this->getForm()->appendField(new Link('cancel', Factory::instance()->getBackUrl()));

        return $this;
    }

    public function execute(array $values = []): static
    {
        $this->init();

        // Load form with object values
        $load = $this->form->unmapValues($this->getUser());
        $load['userId'] = $this->getUser()->userId;
        $load['perm'] = $this->getUser()->getPermissionList();
        $this->form->setFieldValues($load);

        parent::execute($values);

        return $this;
    }

    public function onSubmit(Form $form, SubmitExit $action): void
    {
        // set object values from fields
        $form->mapValues($this->getUser());

        if ($form->getField('currentPass') && $form->getFieldValue('currentPass')) {
            if (!password_verify($form->getFieldValue('currentPass'), $this->getUser()->password)) {
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
            $this->getUser()->password = \Bs\Db\User::hashPassword($form->getFieldValue('newPass'));
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

        $this->getForm()->getField('username')->addFieldCss('col-6');
        $this->getForm()->getField('email')->addFieldCss('col-6');
        $renderer = $this->getRenderer();
        $renderer?->addFieldCss('mb-3');
        return $renderer->show();
    }

    public function getUser(): \Bs\Db\User
    {
        /** @var \Bs\Db\User $obj */
        $obj = $this->getModel();
        return $obj;
    }
}