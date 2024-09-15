<?php
namespace Bs\Form;

use Bs\Db\Permissions;
use Bs\Factory;
use Bs\Form;
use Dom\Template;
use Tk\Alert;
use Tk\Form\Action\Link;
use Tk\Form\Action\SubmitExit;
use Tk\Form\Field\Input;
use Tk\Form\Field\Checkbox;
use Tk\Form\Field\Hidden;
use Tk\Form\Field\Select;
use Tk\Form\Field\Textarea;
use Tk\Uri;

class User extends Form
{
    protected string $type = \Bs\Db\User::TYPE_MEMBER;


    public function init(): static
    {
        $group = 'Details';
        $this->appendField(new Hidden('userId'))->setReadonly();

        $list = \Bs\Db\User::getTitleList();
        $this->appendField(new Select('nameTitle', $list))
            ->setGroup($group)
            ->setLabel('Title')
            ->prependOption('', '');

        $this->appendField(new Input('nameFirst'))
            ->setGroup($group)
            ->setLabel('First Name')
            ->setRequired();

        $this->appendField(new Input('nameLast'))
            ->setGroup($group)
            ->setLabel('Last Name');

//        $this->appendField(new Input('nameDisplay'))
//            ->setGroup($group)
//            ->setLabel('Preferred Name');

        $l1 = $this->appendField(new Input('username'))->setGroup($group)
            ->setRequired();

        $l2 = $this->appendField(new Input('email'))->setGroup($group)
            ->setRequired();

        // Only input lock existing user
        if ($this->getUser()->userId) {
            $l1->addCss('tk-input-lock');
            $l2->addCss('tk-input-lock');
        }

        $factory = Factory::instance();
        $USER = $factory->getAuthUser();
        if ($this->getUser()->isStaff() && $USER->hasPermission(Permissions::PERM_SYSADMIN)) {

            $list = array_flip($factory->getAvailablePermissions($this->getUser()));
            $field = $this->appendField(new Checkbox('perm', $list))
                ->setLabel('Permissions')
                ->setGroup('Permissions')
                ->setNotes('Only admin users can modify permissions');
            if (!$USER->isAdmin()) {   // disable permission change for admin user
                $field->setDisabled();
            }

            $this->appendField(new Checkbox('active', ['Enable User Login' => '1']))
                ->setGroup($group);
        }

        $this->appendField(new Textarea('notes'))
            ->setGroup($group);


        // Form Actions
        $this->appendField(new SubmitExit('save', [$this, 'onSubmit']));
        $this->appendField(new Link('cancel', $factory->getBackUrl()));

        return $this;
    }

    public function execute(array $values = []): static
    {
        $this->init();

        // Load form with object values
        $load = $this->unmapModel($this->getUser());
        $load['perm'] = $this->getUser()->getPermissionList();
        $this->setFieldValues($load);

        parent::execute($values);

        return $this;
    }

    public function onSubmit(Form $form, SubmitExit $action): void
    {
        // non admin cannot change permissions
        if (!Factory::instance()->getAuthUser()->isAdmin()) {
            $form->removeField('perm');
        }

        // set object values from fields
        $form->mapModel($this->getUser());

        if ($form->getField('perm')) {
            $this->getUser()->permissions = array_sum($form->getFieldValue('perm') ?? []);
        }

        $form->addFieldErrors($this->getUser()->validate());
        if ($form->hasErrors()) {
            Alert::addError('Form contains errors.');
            return;
        }

        $isNew = $this->getUser()->userId == 0;
        $this->getUser()->save();

        // Send email to update password
        if ($isNew) {
            if (\Bs\Email\User::sendRecovery($this->getUser())) {
                Alert::addSuccess('An email has been sent to ' . $this->getUser()->email . ' to create their password.');
            } else {
                Alert::addError('Failed to send email to ' . $this->getUser()->email . ' to create their password.');
            }
        }

        Alert::addSuccess('Form save successfully.');
        $action->setRedirect(Uri::create('/user/'.$this->getType().'Edit')->set('userId', $this->getUser()->userId));
        if ($form->getTriggeredAction()->isExit()) {
            $action->setRedirect(Factory::instance()->getBackUrl());
        }
    }

    public function show(): ?Template
    {
        $this->getField('nameTitle')->addFieldCss('col-1');
        $this->getField('nameFirst')->addFieldCss('col-5');
        $this->getField('nameLast')->addFieldCss('col-6');
        //$this->getField('nameDisplay')->addFieldCss('col-5');

        $this->getField('username')->addFieldCss('col-6');
        $this->getField('email')->addFieldCss('col-6');

        $renderer = $this->getRenderer();
        $renderer?->addFieldCss('mb-3');

        return $renderer?->show();
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

    public function getUser(): \Bs\Db\User
    {
        /** @var \Bs\Db\User $obj */
        $obj = $this->getModel();
        return $obj;
    }
}