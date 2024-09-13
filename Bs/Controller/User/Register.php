<?php
namespace Bs\Controller\User;

use Bs\ControllerDomInterface;
use Bs\Db\User;
use Bs\Form;
use Bs\Registry;
use Dom\Template;
use Tk\Alert;
use Tk\Encrypt;
use Tk\Uri;

class Register extends ControllerDomInterface
{

    protected ?Form $form = null;

    public function __construct()
    {
        $this->setPageTemplate($this->getConfig()->get('path.template.login'));
    }

    public function doDefault(): void
    {
        $this->getPage()->setTitle('Register');

        if (!$this->getConfig()->get('user.registration.enable', false)) {
            Alert::addError('User registrations are closed for this account');
            Uri::create('/home')->redirect();
        }

        $this->form = new \Bs\Form\Register();
        $this->form->execute($_POST);

    }

    public function doActivate(): void
    {
        $this->getPage()->setTitle('Register');

        if (!$this->getConfig()->get('user.registration.enable', false)) {
            Alert::addError('New user registrations are closed for this account');
            Uri::create('/home')->redirect();
        }

        //$token = $request->get('t');        // Bug: replaces + with a space on POSTS
        $token = $_REQUEST['t'] ?? '';
        $arr = Encrypt::create($this->getConfig()->get('system.encrypt'))->decrypt($token);
        $arr = unserialize($arr);
        if (!is_array($arr)) {
            Alert::addError('Unknown account registration error, please try again.');
            Uri::create('/home')->redirect();
        }

        $user = User::findByHash($arr['h'] ?? '');
        if (!$user) {
            Alert::addError('Invalid user registration');
            Uri::create('/home')->redirect();
        }

        $user->active = true;
        $user->notes = '';
        $user->save();

        Alert::addSuccess('You account has been successfully activated, please login.');
        Uri::create('/login')->redirect();

    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();

        if ($this->form) {
            $template->appendTemplate('content', $this->form->show());
        }

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
    <h1 class="h3 mb-3 fw-normal text-center">Account Registration</h1>
    <div class="" var="content"></div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}