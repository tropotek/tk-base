<?php
namespace Bs\Form;

use Dom\Template;
use Tk\Alert;
use Tk\Encrypt;
use Tk\Form;
use Tk\Form\Field;
use Tk\Form\Action;
use Tk\Uri;

class Register extends EditInterface
{


    public function init(): void
    {
        // Set a token in the session on show, to ensure this browser is the one that requested the login.
        $this->getSession()->set('recover', time());

        $this->form->appendField(new Field\Input('name'))
            ->setRequired()
            ->setAttr('placeholder', 'Name');

        $this->form->appendField(new Field\Input('email'))
            ->setRequired()
            ->setAttr('placeholder', 'Email');

        $this->form->appendField(new Field\Input('username'))
            ->setAttr('placeholder', 'Username')
            ->setAttr('autocomplete', 'off')
            ->setRequired();

        $this->form->appendField(new Field\Password('password'))
            ->setAttr('placeholder', 'Password')
            ->setAttr('autocomplete', 'off')
            ->setRequired();

        $this->form->appendField(new Field\Password('confPassword'))
            ->setLabel('Password Confirm')
            ->setAttr('placeholder', 'Password Confirm')
            ->setAttr('autocomplete', 'off')
            ->setRequired();

        $html = <<<HTML
            <a href="/recover">Recover</a> | <a href="/login">Login</a>
        HTML;
        $this->getForm()->appendField(new Field\Html('links', $html))->setLabel('')->addFieldCss('text-center');
        $this->getForm()->appendField(new Action\Submit('register', [$this, 'onSubmit']));

    }

    public function execute(array $values = []): void
    {
        $load = [];
        $this->getForm()->setFieldValues($load);
        parent::execute($values);
    }

    public function onSubmit(Form $form, Action\ActionInterface $action)
    {
        if (!$this->getRegistry()->get('site.account.registration', false)) {
            Alert::addError('New user registrations are closed for this account');
            Uri::create('/home')->redirect();
        }

        $user = $this->getFactory()->createUser();
        $user->setActive(false);
        $user->setNotes('pending activation');
        $user->setType(\Bs\Db\User::TYPE_MEMBER);

        $user->getMapper()->getFormMap()->loadObject($user, $form->getFieldValues());

        $token = $this->getSession()->get('recover', 0);
        $this->getSession()->remove('recover');
        if (($token + 60*2) < time()) { // submit before form token times out
            $form->addError('Invalid form submission, please try again.');
            return;
        }

        if (!$form->getFieldValue('password')  || $form->getFieldValue('password') != $form->getFieldValue('confPassword')) {
            $form->addFieldError('password');
            $form->addFieldError('confPassword');
            $form->addFieldError('confPassword', 'Passwords do not match');
        } else {
            if (!$this->getConfig()->isDebug()) {
                $errors = \Bs\Db\User::validatePassword($form->getFieldValue('password'));
                if (count($errors)) {
                    $form->addFieldError('confPassword', implode('<br/>', $errors));
                }
            }
        }

        $form->addFieldErrors($user->validate());

        if ($form->hasErrors()) {
            return;
        }

        $user->setPassword(\Bs\Db\User::hashPassword($user->getPassword()));
        $user->save();

        // send email to user
        $content = <<<HTML
            <h2>Account Activation.</h2>
            <p>
              Welcome {name}
            </p>
            <p>
              Please follow the link to activate your account and finish the user registration.<br/>
              <a href="{activate-url}" target="_blank">{activate-url}</a>
            </p>
            <p><small>Note: If you did not initiate this account creation you can safely disregard this message.</small></p>
        HTML;

        $message = $this->getFactory()->createMessage();
        $message->set('content', $content);
        $message->setSubject($this->getRegistry()->getSiteName() . ' Account Registration');
        $message->addTo($user->getEmail());
        $message->set('name', $user->getName());

        $hashToken = Encrypt::create($this->getConfig()->get('system.encrypt'))
            ->encrypt(serialize(['h' => $user->getHash(), 't' => time()]));
        $url = Uri::create('/registerActivate')->set('t', $hashToken);
        $message->set('activate-url', $url->toString());

        $this->getFactory()->getMailGateway()->send($message);

        Alert::addSuccess('Please check your email for instructions to activate your account.');
        Uri::create('/home')->redirect();
    }

    public function show(): ?Template
    {
        $renderer = $this->getFormRenderer();
        return $renderer->show();
    }

}