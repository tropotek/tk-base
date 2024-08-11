<?php
namespace Bs\Form;

use Bs\Db\User;
use Dom\Template;
use Tk\Alert;
use Tk\Encrypt;
use Tk\Form;
use Tk\Form\Field;
use Tk\Form\Action;
use Tk\Uri;

class RecoverPassword extends EditInterface
{

    protected ?User $user = null;

    protected function initFields(): void
    {
        // logout any existing user
        User::logout();
        $this->getSession()->set('recover', time());

        //$token = $request->get('t');        // Bug in here that replaces + with a space on POSTS
        $token = $_REQUEST['t'] ?? '';
        $arr = Encrypt::create($this->getConfig()->get('system.encrypt'))->decrypt($token);
        $arr = unserialize($arr);
        if (!is_array($arr)) {
            Alert::addError('Unknown account recovery error, please try again.');
            Uri::create('/home')->redirect();
        }

        if ((($arr['t'] ?? 0) + 60*60*24*1) < time()) { // submit before form token times out (1 day)
            Alert::addError('Recovery URL has expired, please try again.');
            Uri::create('/home')->redirect();
        }

        $this->user = $this->getFactory()->getUserMap()->findByHash($arr['h'] ?? '');
        if (!$this->user) {
            Alert::addError('Invalid user token');
            Uri::create('/home')->redirect();
        }

        $this->getForm()->appendField(new Field\Hidden('t'));
        $this->getForm()->appendField(new Field\Password('newPassword'))->setLabel('Password')
            ->setAttr('placeholder', 'Password')
            ->setAttr('autocomplete', 'off')->setRequired();
        $this->getForm()->appendField(new Field\Password('confPassword'))->setLabel('Confirm')
            ->setAttr('placeholder', 'Password Confirm')
            ->setAttr('autocomplete', 'off')->setRequired();

        $this->getForm()->appendField(new Action\Submit('recover-update', [$this, 'onRecover']));
    }

    public function execute(array $values = []): static
    {
        $load = [
            't' => $_REQUEST['t'] ?? ''
        ];
        $this->getForm()->setFieldValues($load);
        parent::execute($values);
        return $this;
    }

    public function onRecover(Form $form, Action\ActionInterface $action): void
    {
        if (!$form->getFieldValue('newPassword')  || $form->getFieldValue('newPassword') != $form->getFieldValue('confPassword')) {
            $form->addFieldError('newPassword');
            $form->addFieldError('confPassword');
            $form->addFieldError('confPassword', 'Passwords do not match');
        } else {
            if (!$this->getConfig()->isDebug()) {
                $errors = User::validatePassword($form->getFieldValue('newPassword'));
                if (count($errors)) {
                    $form->addFieldError('confPassword', implode('<br/>', $errors));
                }
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

    public function show(): ?Template
    {
        $renderer = $this->getFormRenderer();
        return $renderer->show();
    }

}