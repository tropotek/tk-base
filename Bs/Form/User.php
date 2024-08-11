<?php
namespace Bs\Form;

use Dom\Template;
use Tk\Alert;
use Tk\Db\Mapper\Model;
use Tk\Form;
use Tk\Form\Field\Input;
use Tk\Form\Field\Checkbox;
use Tk\Form\Field\Hidden;
use Tk\Uri;

class User extends EditInterface
{
    protected string $type = \Bs\Db\User::TYPE_MEMBER;


    protected function initFields(): void
    {
        $group = 'Details';
        $this->getForm()->appendField(new Hidden('userId'))->setGroup($group);

        $list = \Bs\Db\User::getTitleList();
        $this->getForm()->appendField(new Form\Field\Select('nameTitle', $list))
            ->setGroup($group)
            ->setLabel('Title')
            ->prependOption('', '');

        $this->getForm()->appendField(new Input('nameFirst'))
            ->setGroup($group)
            ->setLabel('First Name')
            ->setRequired();

        $this->getForm()->appendField(new Input('nameLast'))
            ->setGroup($group)
            ->setLabel('Last Name');

//        $this->getForm()->appendField(new Input('nameDisplay'))
//            ->setGroup($group)
//            ->setLabel('Preferred Name');

        $l1 = $this->getForm()->appendField(new Input('username'))->setGroup($group)
            ->setRequired();

        $l2 = $this->getForm()->appendField(new Input('email'))->setGroup($group)
            ->setRequired();

        // Only input lock existing user
        if ($this->getUser()->getId()) {
            $l1->addCss('tk-input-lock');
            $l2->addCss('tk-input-lock');
        }

        if ($this->getUser()->isStaff() && $this->getFactory()->getAuthUser()->hasPermission(\Bs\Db\User::PERM_SYSADMIN)) {

            $list = array_flip($this->getUser()->getAvailablePermissions());
            $field = $this->getForm()->appendField(new Checkbox('perm', $list))
                ->setLabel('Permissions')
                ->setGroup('Permissions');
            if ($this->getUser()->username == 'admin') {   // disable permission change for admin user
                $field->setDisabled();
            }

            $this->getForm()->appendField(new Checkbox('active', ['Enable User Login' => 'active']))
                ->setGroup($group);
        }

        $this->getForm()->appendField(new Form\Field\Textarea('notes'))
            ->setGroup($group);

        $this->getForm()->appendField(new Form\Action\SubmitExit('save', [$this, 'onSubmit']));
        $this->getForm()->appendField(new Form\Action\Link('cancel', $this->getFactory()->getBackUrl()));

    }

    public function execute(array $values = []): static
    {
        $load = $this->getUser()->getMapper()->getFormMap()->getArray($this->getUser());
        $load['userId'] = $this->getUser()->userId;
        $load['perm'] = $this->getUser()->getPermissionList();
        $this->getForm()->setFieldValues($load); // Use form data mapper if loading objects

        parent::execute($values);
        return $this;
    }

    public function onSubmit(Form $form, Form\Action\ActionInterface $action): void
    {
        if ($this->getUser()->username == 'admin') {
            $form->removeField('perm');
        }

        $this->getUser()->getMapper()->getFormMap()->loadObject($this->getUser(), $form->getFieldValues());
        if ($form->getField('perm')) {
            $this->getUser()->permissions = array_sum($form->getFieldValue('perm') ?? []);
        }

        $form->addFieldErrors($this->getUser()->validate());
        if ($form->hasErrors()) {
            Alert::addError('Form contains errors.');
            return;
        }

        $isNew = $this->getUser()->getId() == 0;
        $this->getUser()->save();

        // Send email to update password
        if ($isNew) {
            $this->getUser()->sendRecoverEmail(true);
            Alert::addSuccess('An email has been sent to ' . $this->getUser()->email . ' to create their password.');
        }

        Alert::addSuccess('Form save successfully.');
        $action->setRedirect(Uri::create('/user/'.$this->getType().'Edit')->set('userId', $this->getUser()->userId));
        if ($form->getTriggeredAction()->isExit()) {
            $action->setRedirect($this->getFactory()->getBackUrl());
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

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): User
    {
        $this->type = $type;
        return $this;
    }

    public function getUser(): \Bs\Db\User|Model
    {
        return $this->getModel();
    }
}