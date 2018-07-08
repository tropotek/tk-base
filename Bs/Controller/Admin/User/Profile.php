<?php
namespace Bs\Controller\Admin\User;

use Tk\Request;
use Dom\Template;
use Tk\Form;
use Tk\Form\Field;
use Tk\Form\Event;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Profile extends \Bs\Controller\AdminIface
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
     * @throws \Exception
     */
    public function __construct()
    {
        $this->setPageTitle('My Profile');
        $this->getCrumbs()->reset();
    }

    /**
     *
     * @param Request $request
     * @throws Form\Exception
     * @throws \ReflectionException
     * @throws \Tk\Exception
     */
    public function doDefault(Request $request)
    {
        $this->user = $this->getUser();

        $this->form = $this->getConfig()->createForm('user-edit');
        $this->form->setRenderer($this->getConfig()->createFormRenderer($this->form));

        $this->form->addField(new Field\Html('username'))->setRequired(true);
        $this->form->addField(new Field\Input('email'))->setRequired(true);
        $this->form->addField(new Field\Input('name'))->setRequired(true);

        $this->form->setAttr('autocomplete', 'off');
        $f = $this->form->addField(new Field\Password('newPassword'))->setAttr('placeholder', 'Click to edit')
            ->setAttr('readonly')->setAttr('onfocus', "this.removeAttribute('readonly');this.removeAttribute('placeholder');");
        if (!$this->user->getId())
            $f->setRequired(true);
        $f = $this->form->addField(new Field\Password('confPassword'))->setAttr('placeholder', 'Click to edit')
            ->setAttr('readonly')->setAttr('onfocus', "this.removeAttribute('readonly');this.removeAttribute('placeholder');")
            ->setNotes('Change this users password.');
        if (!$this->user->getId())
            $f->setRequired(true);


        $this->form->addField(new Event\Submit('update', array($this, 'doSubmit')));
        $this->form->addField(new Event\Submit('save', array($this, 'doSubmit')));
        $this->form->addField(new Event\Link('cancel', $this->getCrumbs()->getBackUrl()));

        $this->form->load(\Bs\Db\UserMap::create()->unmapForm($this->user));
        
        $this->form->execute();

    }

    /**
     * @param \Tk\Form $form
     * @param \Tk\Form\Event\Iface $event
     * @throws \ReflectionException
     * @throws \Tk\Db\Exception
     */
    public function doSubmit($form, $event)
    {
        // Load the object with data from the form using a helper object
        \Bs\Db\UserMap::create()->mapForm($form->getValues(), $this->user);

        // Password validation needs to be here
        if ($this->form->getFieldValue('newPassword')) {
            if ($this->form->getFieldValue('newPassword') != $this->form->getFieldValue('confPassword')) {
                $form->addFieldError('newPassword', 'Passwords do not match.');
                $form->addFieldError('confPassword');
            }
        }
        $form->addFieldErrors($this->user->validate());
        
        // Just a small check to ensure the user down not change their own role
        if ($this->user->getId() == $this->getUser()->getId() && $this->user->role != $this->getUser()->role) {
            $form->addError('You cannot change your own role information as this will make the system unstable.');
        }
        if ($this->user->getId() == $this->getUser()->getId() && !$this->user->active) {
            $form->addError('You cannot change your own active status as this will make the system unstable.');
        }
        
        if ($form->hasErrors()) {
            return;
        }

        if ($this->form->getFieldValue('newPassword')) {
            $this->user->setNewPassword($this->form->getFieldValue('newPassword'));
        }

        $this->user->save();

        \Tk\Alert::addSuccess('Record saved!');
        $event->setRedirect(\Tk\Uri::create());
        if ($form->getTriggeredEvent()->getName() == 'update') {
            $event->setRedirect($this->getCrumbs()->getBackUrl());
        }
    }

    /**
     * @return \Dom\Template
     * @throws \Dom\Exception
     */
    public function show()
    {
        $template = parent::show();
        
        // Render the form
        $template->insertTemplate('form', $this->form->getRenderer()->show());
        $template->insertText('username', $this->user->name . ' - [ID ' . $this->user->id . ']');
        
        return $template;
    }


    /**
     * DomTemplate magic method
     *
     * @return Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<HTML
<div>

  <div class="panel panel-default">
    <div class="panel-heading">
      <i class="fa fa-user fa-fw"></i> <span var="username"></span>
    </div>
    <div class="panel-body">
        <div var="form"></div>
    </div>
  </div>
    
</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }

}