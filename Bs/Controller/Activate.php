<?php
namespace Bs\Controller;

use Bs\Uri;
use Tk\Alert;
use Tk\Request;
use Tk\Form;
use Tk\Form\Field;
use Tk\Form\Event;


/**
 * @author Michael Mifsud <http://www.tropotek.com/>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Activate extends Iface
{

    /**
     * @var Form
     */
    protected $form = null;

    /**
     * @var \Bs\Db\UserIface
     */
    protected $user = null;


    /**
     * Login constructor.
     */
    public function __construct()
    {
        $this->setPageTitle('Activate');
    }

    /**
     * @return \Tk\Controller\Page
     */
    public function getPage()
    {
        if (!$this->page) {
            $templatePath = '';
            if ($this->getConfig()->get('template.login')) {
                $templatePath = $this->getConfig()->getSitePath() . $this->getConfig()->get('template.login');
            }
            $this->page = $this->getConfig()->getPage($templatePath);
        }
        return parent::getPage();
    }

    /**
     * @param string $hash
     * @return \Bs\Db\User|\Tk\Db\Map\Model|\Uni\Db\User|null
     */
    protected function findUser($hash)
    {
        $user = $this->getConfig()->getUserMapper()->findByHash($hash);
        if ($user && !$this->getConfig()->getUserMapper()->hasRecover($user->getId())) {
            $user = null;
        }
        return $user;
    }

    /**
     * @param Request $request
     * @throws \Exception
     */
    public function doDefault(Request $request)
    {
        $hash = $request->get('h');
        $this->user = $this->findUser($hash);
        if (!$this->user) {
            Alert::addError('Invalid user account');
            Uri::create('/')->redirect();
        }

        $this->init();

        $this->form->execute();

    }

    /**
     * @throws \Exception
     */
    protected function init()
    {

        if (!$this->form) {
            $this->form = $this->getConfig()->createForm('activate-form');
        }

        $this->form->appendField(new Field\Input('newPassword'));
        $this->form->appendField(new Field\Input('confPassword'));

        $this->form->appendField(new Event\Submit('activate', array($this, 'doActivate')))->removeCss('btn-default')->addCss('btn btn-lg btn-primary btn-ss');

    }

    public function getLoginUrl()
    {
        return $this->getConfig()->get('url.auth.login');
    }

    /**
     * @param \Tk\Form $form
     * @param \Tk\Form\Event\Iface $event
     */
    public function doActivate($form, $event)
    {
        try {
            if (!$form->getFieldValue('newPassword')  || $form->getFieldValue('newPassword') != $form->getFieldValue('confPassword')) {
                $form->addFieldError('newPassword');
                $form->addFieldError('confPassword');
                $form->addError('Passwords do not match');
            } else {
                if (!$this->getConfig()->isDebug()) {
                    $this->checkPassword($form->getFieldValue('newPassword'), $errors);
                    if (count($errors)) {
                        $form->addError($errors);
                    }
                }
            }

            if ($form->hasErrors()) {
                return;
            }
            $this->user->setNewPassword($form->getFieldValue('newPassword'));
            $this->user->save();
            // remove activation flag from DB
            $this->getConfig()->getUserMapper()->removeRecover($this->user->getId());


            \Tk\Alert::addSuccess('Password Saved!');
            $event->setRedirect($this->getLoginUrl());
        } catch (\Exception $e) {
            $form->addError($e->getMessage());
            $form->addError('Activation Error: ' . $e->getMessage());
        }
    }

    protected function checkPassword($pwd, &$errors) {
        $errors_init = $errors;

        if (strlen($pwd) < 8) {
            $errors[] = "Password too short!";
        }

        if (!preg_match("#[0-9]+#", $pwd)) {
            $errors[] = "Password must include at least one number!";
        }

        if (!preg_match("#[a-zA-Z]+#", $pwd)) {
            $errors[] = "Password must include at least one letter!";
        }

        if( !preg_match("#[A-Z]+#", $pwd) ) {
            $errors[] = "Password must include at least one Capital!";
        }

        if( !preg_match("#\W+#", $pwd) ) {
            $errors[] = "Password must include at least one symbol!";
        }

        return ($errors == $errors_init);
    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $template = parent::show();

        // Render the form
        if ($this->form) {
            $template->appendTemplate('form', $this->form->getRenderer()->show());
        }

        $js = <<<JS
jQuery(function ($) {
  
  $('#activate-form').on('keypress', function (e) {
    if (e.which === 13) {
      $(this).find('#activate-form_activate').trigger('click');
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
  <p>Please create a new password to access your account.</p>
  <p><small>Passwords must be longer than 8 characters and include one number, one uppercase letter and one symbol.</small></p>
  <div var="form"></div>

</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }
}