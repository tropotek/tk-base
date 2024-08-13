<?php
namespace Bs\Form;

use Bs\Db\User;
use Bs\Form;
use Tk\Alert;
use Tk\Auth\Result;
use Tk\Date;
use Tk\Form\Action\Submit;
use Tk\Form\Field\Checkbox;
use Tk\Form\Field\Html;
use Tk\Form\Field\Input;
use Tk\Form\Field\Password;
use Tk\Log;
use Tk\Uri;

class Login extends Form
{

    public function init(): static
    {
        // Set a token in the session on show, to ensure this browser is the one that requested the login.
        $this->getSession()->set('login', time());

        // check if user already logged in...
        $user = User::retrieveMe();
        if ($user) {    // remembered user already logged in
            Alert::addSuccess('Logged in successfully');
            Uri::create('/')->redirect();
        }

        $this->getForm()->appendField(new Input('username'))
            ->setRequired()
            ->setAttr('placeholder', 'Username');

        $this->getForm()->appendField(new Password('password'))
            ->setRequired()
            ->setAttr('placeholder', 'Password');

        $this->getForm()->appendField(new Checkbox('remember', ['Remember me' => 'remember']))
            ->setLabel('');

        $html = <<<HTML
            <a href="/recover">Recover</a>
        HTML;
        if ($this->getRegistry()->get('site.account.registration', false)) {
            $html = <<<HTML
                <a href="/recover">Recover</a> | <a href="/register">Register</a>
            HTML;
        }
        $this->getForm()->appendField(new Html('links', $html))->setLabel('')->addFieldCss('text-center');
        $this->getForm()->appendField(new Submit('login', [$this, 'onSubmit']));

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
        $values = $form->getFieldValues();

        $token = $this->getSession()->get('login', 0);
        $this->getSession()->remove('login');
        if (($token + 60*2) < time()) { // login before form token times out
            $form->addError( 'Invalid form submission, please try again.');
            return;
        }

        $result = $this->getFactory()->getAuthController()->authenticate($this->getFactory()->getAuthAdapter());
        if ($result->getCode() != Result::SUCCESS) {
            Log::error($result->getMessage());
            $form->addError('Invalid login details.');
            return;
        }

        // Login successful
        $user = $this->getFactory()->getAuthUser();
        $user->lastLogin = Date::create('now', $user->timezone ?: null);
        $user->sessionId = $this->getSession()->getId();
        $user->save();

        if (!empty($values['remember'] ?? '')) {
            $user->rememberMe();
        } else {
            $user->forgetMe();
        }

        Uri::create('/')->redirect();
    }

}