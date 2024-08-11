<?php
namespace Bs\Form;

use Bs\Db\User;
use Dom\Template;
use Tk\Alert;
use Tk\Auth\Result;
use Tk\Date;
use Tk\Form;
use Tk\Form\Field;
use Tk\Form\Action;
use Tk\Log;
use Tk\Uri;

class Login extends EditInterface
{

    protected function initFields(): void
    {
        // Set a token in the session on show, to ensure this browser is the one that requested the login.
        $this->getSession()->set('login', time());

        // check if user already logged in...
        $user = User::retrieveMe();
        if ($user) {    // remembered user already logged in
            Alert::addSuccess('Logged in successfully');
            Uri::create('/')->redirect();
        }

        $this->getForm()->appendField(new Field\Input('username'))
            ->setRequired()
            ->setAttr('placeholder', 'Username');

        $this->getForm()->appendField(new Field\Password('password'))
            ->setRequired()
            ->setAttr('placeholder', 'Password');

        $this->getForm()->appendField(new Field\Checkbox('remember', ['Remember me' => 'remember']))
            ->setLabel('');

        $html = <<<HTML
            <a href="/recover">Recover</a>
        HTML;
        if ($this->getRegistry()->get('site.account.registration', false)) {
            $html = <<<HTML
                <a href="/recover">Recover</a> | <a href="/register">Register</a>
            HTML;
        }
        $this->getForm()->appendField(new Field\Html('links', $html))->setLabel('')->addFieldCss('text-center');
        $this->getForm()->appendField(new Action\Submit('login', [$this, 'onSubmit']));

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

    public function show(): ?Template
    {
        $renderer =$this->getFormRenderer();
        return $renderer->show();
    }

}