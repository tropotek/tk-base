<?php
namespace Bs\Form;

use Dom\Template;
use Tk\Alert;
use Bs\Form;
use Tk\Form\Action\Submit;
use Tk\Form\Field\Html;
use Tk\Form\Field\Input;
use Tk\Form\Field\Password;
use Tk\Uri;

class Register extends Form
{

    public function init(): static
    {
        // Set a token in the session on show, to ensure this browser is the one that requested the login.
        $_SESSION['recover'] = time();

        $this->appendField(new Input('name'))
            ->setRequired()
            ->setAttr('placeholder', 'Name');

        $this->appendField(new Input('email'))
            ->setRequired()
            ->setAttr('placeholder', 'Email');

        $this->appendField(new Input('username'))
            ->setAttr('placeholder', 'Username')
            ->setAttr('autocomplete', 'off')
            ->setRequired();

        $this->appendField(new Password('password'))
            ->setAttr('placeholder', 'Password')
            ->setAttr('autocomplete', 'off')
            ->setRequired();

        $this->appendField(new Password('confPassword'))
            ->setLabel('Password Confirm')
            ->setAttr('placeholder', 'Password Confirm')
            ->setAttr('autocomplete', 'off')
            ->setRequired();

        $html = <<<HTML
            <a href="/recover">Recover</a> | <a href="/login">Login</a>
        HTML;
        $this->getForm()->appendField(new Html('links', $html))->setLabel('')->addFieldCss('text-center');
        $this->getForm()->appendField(new Submit('register', [$this, 'onSubmit']));

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
        if (!$this->getRegistry()->get('site.account.registration', false)) {
            Alert::addError('New user registrations are closed for this account');
            Uri::create('/')->redirect();
        }

        $user = \Bs\Db\User::create();
        $user->active = false;
        $user->notes = 'pending activation';
        $user->type = \Bs\Db\User::TYPE_MEMBER;

        // set object values from fields
        $form->mapValues($user);

        $token = $_SESSION['recover'] ?? 0;
        unset($_SESSION['recover']);

        if (($token + 60*2) < time()) { // submit before form token times out
            $form->addError('Invalid form submission, please try again.');
            return;
        }

        if (!$form->getFieldValue('password')  || $form->getFieldValue('password') != $form->getFieldValue('confPassword')) {
            $form->addFieldError('password');
            $form->addFieldError('confPassword');
            $form->addFieldError('confPassword', 'Passwords do not match');
        } else {
            $errors = \Bs\Db\User::validatePassword($form->getFieldValue('password'));
            if (count($errors)) {
                $form->addFieldError('confPassword', implode('<br/>', $errors));
            }
        }

        $form->addFieldErrors($user->validate());

        if ($form->hasErrors()) {
            return;
        }

        $user->password = \Bs\Db\User::hashPassword($user->password);
        $user->save();

        \Bs\Email\User::sendRegister($user);

        Alert::addSuccess('Please check your email for instructions to activate your account.');
        Uri::create('/')->redirect();
    }

    public function show(): ?Template
    {
        return $this->getRenderer()?->show();
    }

}