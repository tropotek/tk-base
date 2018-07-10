<?php
namespace Bs\Controller;

use Tk\Form;
use Tk\Form\Field;
use Tk\Form\Event;
use Tk\Request;
use Tk\Auth\AuthEvents;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Register extends Iface
{
    /**
     * @var Form
     */
    protected $form = null;

    /**
     * @var \Bs\Db\User
     */
    private $user = null;



    /**
     * Login constructor.
     */
    public function __construct()
    {
        $this->setPageTitle('Register New Account');
    }

    /**
     * @param Request $request
     * @throws \Exception
     * @throws \Tk\Exception
     */
    public function doDefault(Request $request)
    {
        if (!$this->getConfig()->get('site.client.registration')) {
            \Tk\Alert::addError('User registration has been disabled on this site.');
            \Tk\Uri::create('/')->redirect();
        }
        if ($request->has('h')) {
            $this->doConfirmation($request);
        }

        $this->user = new \Bs\Db\User();
        $this->user->role = \Bs\Db\User::ROLE_USER;

        $this->form = $this->getConfig()->createForm('register-account');
        $this->form->setRenderer($this->getConfig()->createFormRenderer($this->form));

        $this->form->addField(new Field\Input('name'));
        $this->form->addField(new Field\Input('email'));
        $this->form->addField(new Field\Input('username'));
        $this->form->addField(new Field\Password('password'));
        $this->form->addField(new Field\Password('passwordConf'))->setLabel('Password Confirm');
        $this->form->addField(new Event\Submit('register', array($this, 'doRegister')))->addCss('btn btn-lg btn-primary btn-ss');
        $this->form->addField(new Event\Link('forgotPassword', \Tk\Uri::create('/recover.html'), ''))
            ->removeCss('btn btn-sm btn-default btn-once');

        $this->form->load(\Bs\Db\UserMap::create()->unmapForm($this->user));
        $this->form->execute();
    }


    /**
     * @param \Tk\Form $form
     * @param \Tk\Form\Event\Iface $event
     * @throws \ReflectionException
     * @throws \Tk\Db\Exception
     */
    public function doRegister($form, $event)
    {
        \Bs\Db\UserMap::create()->mapForm($form->getValues(), $this->user);

        if (!$this->form->getFieldValue('password')) {
            $form->addFieldError('password', 'Please enter a password');
            $form->addFieldError('passwordConf');
        }
        // Check the password strength, etc....
        if (!preg_match('/.{6,32}/', $this->form->getFieldValue('password'))) {
            $form->addFieldError('password', 'Please enter a valid password');
            $form->addFieldError('passwordConf');
        }
        // Password validation needs to be here
        if ($this->form->getFieldValue('password') != $this->form->getFieldValue('passwordConf')) {
            $form->addFieldError('password', 'Passwords do not match.');
            $form->addFieldError('passwordConf');
        }

        $form->addFieldErrors($this->user->validate());

        if ($form->hasErrors()) {
            return;
        }

        // Create a user and make a temp hash until the user activates the account
        $hash = $this->user->generateHash(true);
        $this->user->hash = $hash;
        $this->user->active = false;
        $this->user->setNewPassword($this->user->password);
        $this->user->save();

        // Fire the login event to allow developing of misc auth plugins
        $e = new \Tk\Event\Event();
        $e->set('form', $form);
        $e->set('user', $this->user);
        $this->getConfig()->getEventDispatcher()->dispatch(AuthEvents::REGISTER, $e);


        // Redirect with message to check their email
        \Tk\Alert::addSuccess('Your New Account Has Been Created.');
        \Tk\Config::getInstance()->getSession()->set('h', $this->user->hash);
        $event->setRedirect(\Tk\Uri::create());
    }

    /**
     * Activate the user account if not activated already, then trash the request hash....
     *
     * @param Request $request
     * @throws \Tk\Db\Exception
     * @throws \Tk\Exception
     */
    public function doConfirmation($request)
    {
        // Receive a users on confirmation and activate the user account.
        $hash = $request->get('h');
        if (!$hash) {
            throw new \InvalidArgumentException('Cannot locate user. Please contact administrator.');
        }
        /** @var \Bs\Db\User $user */
        $user = \Bs\Db\UserMap::create()->findByHash($hash);
        if (!$user) {
            throw new \InvalidArgumentException('Cannot locate user. Please contact administrator.');
        }
        $user->hash = $user->generateHash();
        $user->active = true;
        $user->save();

        $event = new \Tk\Event\Event();
        $event->set('user', $user);
        $this->getConfig()->getEventDispatcher()->dispatch(AuthEvents::REGISTER_CONFIRM, $event);

        \Tk\Alert::addSuccess('Account Activation Successful.');
        \Tk\Uri::create('/login.html')->redirect();

    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $template = parent::show();

        if ($this->getConfig()->get('site.client.registration')) {
            $template->setChoice('register');
        }

        if ($this->getConfig()->getSession()->getOnce('h')) {
            $template->setChoice('success');

        } else {
            $template->setChoice('form');
            // Render the form
            $template->insertTemplate('form', $this->form->getRenderer()->show());
        }

        return $template;
    }

}