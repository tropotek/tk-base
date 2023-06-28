<?php
namespace Bs\Form;

use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Alert;
use Tk\Form;
use Tk\FormRenderer;
use Tk\Form\Field\Input;
use Tk\Form\Field\Checkbox;
use Tk\Form\Field\Hidden;
use Tk\Traits\SystemTrait;
use Tk\Uri;

class Profile
{
    use SystemTrait;
    use Form\FormTrait;

    protected \Bs\Db\User $user;


    public function __construct()
    {
        $this->setForm(Form::create('user'));
        $this->user = $this->getFactory()->getAuthUser();
    }

    public function doDefault(Request $request)
    {
        $tab = 'Details';
        $this->getForm()->appendField(new Hidden('id'))->setGroup($tab);

        $this->getForm()->appendField(new Input('name'))->setGroup($tab)
            ->setRequired();

        $this->getForm()->appendField(new Input('username'))->setGroup($tab)
            ->setDisabled()
            ->setReadonly();

        $this->getForm()->appendField(new Input('email'))->setGroup($tab)
            ->addCss('tk-input-lock')
            ->setRequired();

        if ($this->user->isType(\Bs\Db\User::TYPE_STAFF)) {
            $this->getForm()->appendField(new Checkbox('perm', array_flip($this->user->getAvailablePermissions())))
                ->setGroup($tab)
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

        $load = $this->getUser()->getMapper()->getFormMap()->getArray($this->getUser());
        $load['id'] = $this->getUser()->getId();
        $load['perm'] = $this->getUser()->getPermissionList();
        $this->getForm()->setFieldValues($load); // Use form data mapper if loading objects

        $this->getForm()->execute($request->request->all());

        $this->setFormRenderer(new FormRenderer($this->getForm()));

    }

    public function onSubmit(Form $form, Form\Action\ActionInterface $action)
    {
        $this->getUser()->getMapper()->getFormMap()->loadObject($this->user, $form->getFieldValues());

        if ($form->getField('currentPass') && $form->getFieldValue('currentPass')) {
            if (!password_verify($form->getFieldValue('currentPass'), $this->user->getPassword())) {
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

        $form->addFieldErrors($this->user->validate());
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
        // Setup field group widths with bootstrap classes
        $this->getForm()->getField('username')->addFieldCss('col-6');
        $this->getForm()->getField('email')->addFieldCss('col-6');

        $renderer = $this->getFormRenderer();
        $renderer->addFieldCss('mb-3');

        return $renderer->show();
    }

    public function getUser(): \Bs\Db\User
    {
        return $this->user;
    }
}