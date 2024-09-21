<?php
namespace Bs\Form;

use Bs\Db\User;
use Bs\Form;
use Bs\Registry;
use Tk\Alert;
use Tk\Form\Action\Submit;
use Tk\Form\Field\Html;
use Tk\Form\Field\Input;
use Tk\Uri;

class Recover extends Form
{

    public function init(): static
    {
        // logout any existing user
        User::logout();


        $this->getForm()->appendField(new Input('username'))
            ->setAttr('autocomplete', 'off')
            ->setAttr('placeholder', 'Username')
            ->setRequired()
            ->setNotes('Enter your username to recover access your account.');

        $html = <<<HTML
            <a href="/login">Login</a>
        HTML;
        if ($this->getConfig()->get('user.registration.enable', false)) {
            $html = <<<HTML
                <a href="/register">Register</a> | <a href="/login">Login</a>
            HTML;

        }
        $this->getForm()->appendField(new Html('links', $html))->setLabel('')->addFieldCss('text-center');
        $this->getForm()->appendField(new Submit('recover', [$this, 'onSubmit']));

        return $this;
    }

    public function execute(array $values = []): static
    {
        $this->init();

        $load = [];
        $this->getForm()->setFieldValues($load);
        parent::execute($values);

        return $this;
    }

    public function onSubmit(Form $form, Submit $action): void
    {
        if (!$form->getFieldValue('username')) {
            $form->setFieldValue('username', '');
            $form->addError('Please enter a valid username.');
            return;
        }

        $user = User::findByUsername(strtolower($form->getFieldValue('username')));
        if (!$user) {
            $form->setFieldValue('username', '');
            $form->addFieldError('username', 'Please enter a valid username.');
            return;
        }

        if (\Bs\Email\User::sendRecovery($user)) {
            Alert::addSuccess('Please check your email for instructions to recover your account.');
        } else {
            Alert::addWarning('Recovery email failed to send. Please <a href="/contact">contact us.</a>');
        }

        Uri::create('/')->redirect();
    }

}