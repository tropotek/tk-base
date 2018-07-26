<?php
namespace Bs\Controller;

use Tk\Request;
use Tk\Form;
use Tk\Form\Field;
use Tk\Form\Event;
use Tk\Auth\AuthEvents;
use Tk\Event\AuthEvent;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Login extends Iface
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
        $this->setPageTitle('Login');
    }

    /**
     * @param Request $request
     * @throws \Exception
     */
    public function doDefault(Request $request)
    {

        $this->form = $this->getConfig()->createForm('login-form');
        $this->form->setRenderer($this->getConfig()->createFormRenderer($this->form));

        $this->form->addField(new Field\Input('username'));
        $this->form->addField(new Field\Password('password'));
        $this->form->addField(new Event\Submit('login', array($this, 'doLogin')))->addCss('btn btn-lg btn-primary btn-ss');
        $this->form->addField(new Event\Link('forgotPassword', \Tk\Uri::create($this->getConfig()->get('url.auth.recover')), ''))
            ->removeCss('btn btn-sm btn-default btn-once');

        $this->form->execute();

    }

    /**
     * @param \Tk\Form $form
     * @param \Tk\Form\Event\Iface $event
     * @throws \Tk\Db\Exception
     */
    public function doLogin($form, $event)
    {
        if (!$form->getFieldValue('username')) {
            $form->addFieldError('username', 'Please enter a valid username');
        }
        if (!$form->getFieldValue('password')) {
            $form->addFieldError('password', 'Please enter a valid password');
        }

        if ($form->hasErrors()) {
            return;
        }

        try {
            // Fire the login event to allow developing of misc auth plugins
            $e = new AuthEvent();
            $e->replace($form->getValues());
            $this->getConfig()->getEventDispatcher()->dispatch(AuthEvents::LOGIN, $e);

            // Use the event to process the login like below....
            $result = $e->getResult();
            if (!$result) {
                $form->addError('Invalid username or password');
                return;
            }
            if (!$result->isValid()) {
                $form->addError( implode("<br/>\n", $result->getMessages()) );
                return;
            }

            // Copy the event to avoid propagation issues
            $e2 = new AuthEvent($e->getAdapter());
            $e2->replace($e->all());
            $e2->setResult($e->getResult());
            $e2->setRedirect($e->getRedirect());
            $this->getConfig()->getEventDispatcher()->dispatch(AuthEvents::LOGIN_SUCCESS, $e2);
            if ($e2->getRedirect())
                $e2->getRedirect()->redirect();

        } catch (\Exception $e) {
            $form->addError($e->getMessage());
        }
    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $template = parent::show();

        // Render the form
        $template->insertTemplate('form', $this->form->getRenderer()->show());

        if ($this->getConfig()->get('site.client.registration')) {
            $template->setChoice('register');
        }

        $js = <<<JS
jQuery(function ($) {
  
  $('#login-form').on("keypress", function (e) {
    if (e.which === 13) {
      $(this).find('#login-form_login').trigger('click');
    }
  });
  
});
JS;
        $template->appendJs($js);

        return $template;
    }


    /**
     * @return \Dom\Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<HTML
<div class="tk-login-panel tk-login">

  <div var="form"></div>
  <div class="not-member" choice="register">
    <p>Not a member? <a href="/register.html">Register here</a></p>
  </div>

</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }
}