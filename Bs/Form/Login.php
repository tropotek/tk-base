<?php
namespace Bs\Form;

use Bs\Db\User;
use Bs\Form;
use Bs\Registry;
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
        if ($this->getConfig()->get('user.registration.enable', false)) {
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

        $factory = \Bs\Factory::instance();
        $result = $factory->getAuthController()->authenticate($factory->getAuthAdapter());
        if ($result->getCode() != Result::SUCCESS) {
            Log::debug($result->getMessage());
            $form->addError('Invalid login details.');
            return;
        }

        // Login successful
        $user = $factory->getAuthUser();
        $user->lastLogin = Date::create('now', $user->timezone ?: null);
        $user->sessionId = session_id();
        $user->save();

        if (!empty($values['remember'] ?? '')) {
            $user->rememberMe();
        } else {
            $user->forgetMe();
        }

        if ($user instanceof User) {
            $user->getHomeUrl()->redirect();
        }
        Uri::create('/')->redirect();
    }

}