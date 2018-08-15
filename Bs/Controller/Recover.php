<?php
namespace Bs\Controller;

use Tk\Form;
use Tk\Form\Field;
use Tk\Form\Event;
use Tk\Request;
use Tk\Auth\AuthEvents;


/**
 * Class Index
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Recover extends Iface
{

    /**
     * @var Form
     */
    protected $form = null;



    /**
     * Login constructor.
     */
    public function __construct()
    {
        $this->setPageTitle('Recover Password');
    }

    /**
     * @param Request $request
     * @throws Form\Exception
     * @throws \Exception
     */
    public function doDefault(Request $request)
    {
        $this->form = $this->getConfig()->createForm('recover-account');
        $this->form->setRenderer($this->getConfig()->createFormRenderer($this->form));

        $this->form->appendField(new Field\Input('account'))->setLabel('Username / Email');
        $this->form->appendField(new Event\Submit('recover', array($this, 'doRecover')))->addCss('btn btn-lg btn-default btn-ss');
        $this->form->appendField(new Event\Link('login', \Tk\Uri::create($this->getConfig()->get('url.auth.login')), ''))
            ->removeCss('btn btn-sm btn-default btn-once');

        $this->form->execute();

    }

    /**
     * @param Form $form
     * @param \Tk\Form\Event\Iface $event
     * @throws \Exception
     */
    public function doRecover($form, $event)
    {

        if ($form->hasErrors()) {
            $form->addError('Please enter a valid username or email');
            return;
        }

        // TODO: This should be made a bit more secure for larger sites.
        $account = $form->getFieldValue('account');
        /** @var \Bs\Db\User $user */
        $user = null;
        if (filter_var($account, FILTER_VALIDATE_EMAIL)) {
            $user = $this->getConfig()->getUserMapper()->findByEmail($account);
        } else {
            $user = $this->getConfig()->getUserMapper()->findByUsername($account);
        }
        if (!$user) {
            $form->addError('Please enter a valid username or email');
            return;
        }

        $newPass = $this->getConfig()->generatePassword();
        $user->password = $this->getConfig()->hashPassword($newPass, $user);
        $user->save();

        // Fire the login event to allow developing of misc auth plugins
        $e = new \Tk\Event\Event();
        $e->set('form', $form);
        $e->set('user', $user);
        $e->set('password', $newPass);
        //$event->set('templatePath', $this->getTemplatePath());
        $this->getConfig()->getEventDispatcher()->dispatch(AuthEvents::RECOVER, $e);

        \Tk\Alert::addSuccess('You new access details have been sent to your email address.');
        $event->setRedirect(\Tk\Uri::create());
    }


    public function show()
    {
        $template = parent::show();

        // Render the form
        if ($this->form && $this->form->getRenderer() instanceof \Tk\Form\Renderer\Dom) {
            $template->appendTemplate('form', $this->form->getRenderer()->show());
        }

        if ($this->getConfig()->get('site.client.registration')) {
            $template->setChoice('register');
        }

        return $template;
    }


    /**
     * @return \Dom\Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<HTML
<div class="tk-login-panel tk-recover">

  <div var="form"></div>
  <div class="not-member" choice="register">
    <p>Not a member? <a href="/register.html">Register here</a></p>
  </div>

</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }
}