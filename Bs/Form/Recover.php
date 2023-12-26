<?php
namespace Bs\Form;

use Bs\Db\User;
use Dom\Template;
use Tk\Alert;
use Tk\Form;
use Tk\Form\Field;
use Tk\Form\Action;
use Tk\Uri;

class Recover extends EditInterface
{

    protected function initFields(): void
    {
        // logout any existing user
        User::logout();
        $this->getSession()->set('recover', time());

        $this->getForm()->appendField(new Field\Input('username'))
            ->setAttr('autocomplete', 'off')
            ->setAttr('placeholder', 'Username')
            ->setRequired()
            ->setNotes('Enter your username to recover access your account.');

        $html = <<<HTML
            <a href="/login">Login</a>
        HTML;
        if ($this->getRegistry()->get('site.account.registration', false)) {
            $html = <<<HTML
                <a href="/register">Register</a> | <a href="/login">Login</a>
            HTML;

        }
        $this->getForm()->appendField(new Field\Html('links', $html))->setLabel('')->addFieldCss('text-center');
        $this->getForm()->appendField(new Action\Submit('recover', [$this, 'onSubmit']));

    }

    public function execute(array $values = []): static
    {
        $load = [];
        $this->getForm()->setFieldValues($load);
        parent::execute($values);
        return $this;
    }

    public function onSubmit(Form $form, Action\ActionInterface $action): void
    {
        if (!$form->getFieldValue('username')) {
            $form->addError('Please enter a valid username.');
            return;
        }

        $token = $this->getSession()->get('recover', 0);
        $this->getSession()->remove('recover');
        if (($token + 60*2) < time()) { // submit before form token times out
            $form->addError('Invalid form submission, please try again.');
            return;
        }

        $user = $this->getFactory()->getUserMap()->findByUsername(strtolower($form->getFieldValue('username')));
        if (!$user) {
            $form->addFieldError('username', 'Please enter a valid username.');
            return;
        }

        if ($user->sendRecoverEmail()) {
            Alert::addSuccess('Please check your email for instructions to recover your account.');
        } else {
            Alert::addWarning('Recovery email failed to send. Please <a href="/contact">contact us.</a>');
        }

        Uri::create('/home')->redirect();
    }

    public function show(): ?Template
    {
        $renderer = $this->getFormRenderer();
        return $renderer->show();
    }

}