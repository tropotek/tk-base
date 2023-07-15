<?php
namespace Bs\Controller\User;

use Bs\Db\UserMap;
use Bs\Form\EditTrait;
use Dom\Mvc\PageController;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Alert;
use Tk\Encrypt;
use Tk\Uri;

class Register extends PageController
{
    use EditTrait;


    public function __construct()
    {
        parent::__construct($this->getFactory()->getLoginPage());
        $this->getPage()->setTitle('Register');
    }

    public function doDefault(Request $request): \App\Page|\Dom\Mvc\Page
    {
        if (!$this->getRegistry()->get('site.account.registration', false)) {
            Alert::addError('New user registrations are closed for this account');
            Uri::create('/home')->redirect();
        }

        $this->setForm(new \Bs\Form\Register());
        $this->getForm()->init()->execute($request->request->all());

        return $this->getPage();
    }

    public function doActivate(Request $request)
    {
        if (!$this->getRegistry()->get('site.account.registration', false)) {
            Alert::addError('New user registrations are closed for this account');
            Uri::create('/home')->redirect();
        }

        //$token = $request->get('t');        // Bug in here that replaces + with a space on POSTS
        $token = $_REQUEST['t'] ?? '';
        $arr = Encrypt::create($this->getConfig()->get('system.encrypt'))->decrypt($token);
        $arr = unserialize($arr);
        if (!is_array($arr)) {
            Alert::addError('Unknown account registration error, please try again.');
            Uri::create('/home')->redirect();
        }

        $user = $this->getFactory()->getUserMap()->findByHash($arr['h'] ?? '');
        if (!$user) {
            Alert::addError('Invalid user registration');
            Uri::create('/home')->redirect();
        }

        $user->setActive(true);
        $user->setNotes('');
        $user->save();

        Alert::addSuccess('You account has been successfully activated, please login.');
        Uri::create('/login')->redirect();

        return $this->getPage();
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


