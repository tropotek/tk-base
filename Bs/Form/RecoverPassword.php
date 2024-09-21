<?php
namespace Bs\Form;

use Bs\Db\User;
use Bs\Form;
use Tk\Alert;
use Tk\Config;
use Tk\Encrypt;
use Tk\Form\Action\Submit;
use Tk\Form\Field\Hidden;
use Tk\Form\Field\Password;
use Tk\Uri;

class RecoverPassword extends Form
{

    protected ?User $user = null;

    public function init(): static
    {
        // logout any existing user
        User::logout();

        $_SESSION['recover'] = time();

        $token = $_REQUEST['t'] ?? '';
        $arr = Encrypt::create(Config::instance()->get('system.encrypt'))->decrypt($token);
        $arr = unserialize($arr);
        if (!is_array($arr)) {
            Alert::addError('Unknown account recovery error, please try again.');
            Uri::create('/')->redirect();
        }

        if ((($arr['t'] ?? 0) + 60*60*24*1) < time()) { // submit before form token times out (1 day)
            Alert::addError('Recovery URL has expired, please try again.');
            Uri::create('/home')->redirect();
        }

        $this->user = User::findByHash($arr['h'] ?? '');
        if (!$this->user || !$this->user->active) {
            Alert::addError('Invalid user token');
            Uri::create('/home')->redirect();
        }

        $this->getForm()->appendField(new Hidden('t'));
        $this->getForm()->appendField(new Password('newPassword'))->setLabel('Password')
            ->setAttr('placeholder', 'Password')
            ->setAttr('autocomplete', 'off')->setRequired();
        $this->getForm()->appendField(new Password('confPassword'))->setLabel('Confirm')
            ->setAttr('placeholder', 'Password Confirm')
            ->setAttr('autocomplete', 'off')->setRequired();

        $this->getForm()->appendField(new Submit('recover-update', [$this, 'onRecover']));

        return $this;
    }

    public function execute(array $values = []): static
    {
        $this->init();

        $load = [
            't' => $_REQUEST['t'] ?? ''
        ];
        $this->getForm()->setFieldValues($load);
        parent::execute($values);

        return $this;
    }

    public function onRecover(Form $form, Submit $action): void
    {
        if (!$form->getFieldValue('newPassword')  || $form->getFieldValue('newPassword') != $form->getFieldValue('confPassword')) {
            $form->addFieldError('newPassword');
            $form->addFieldError('confPassword');
            $form->addFieldError('confPassword', 'Passwords do not match');
        } else {
            $errors = User::validatePassword($form->getFieldValue('newPassword'));
            if (count($errors)) {
                $form->addFieldError('confPassword', implode('<br/>', $errors));
            }
        }

        if ($form->hasErrors()) {
            return;
        }

        $this->user->password = User::hashPassword($form->getFieldValue('newPassword'));
        $this->user->save();

        Alert::addSuccess('Successfully account recovery. Please login.');
        Uri::create('/login')->redirect();
    }

}