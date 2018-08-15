<?php
namespace Bs\Controller\Admin;

use Tk\Request;
use Tk\Form;
use Tk\Form\Event;
use Tk\Form\Field;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Settings extends \Bs\Controller\AdminIface
{

    /**
     * @var Form
     */
    protected $form = null;

    /**
     * @var \Tk\Db\Data
     */
    protected $data = null;

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        $this->setPageTitle('Settings');
        $this->getCrumbs()->reset();
    }

    /**
     * doDefault
     *
     * @param Request $request
     * @return void
     * @throws \Exception
     */
    public function doDefault(Request $request)
    {

        $this->data = \Tk\Db\Data::create();

        $this->form = $this->getConfig()->createForm('formEdit');
        $this->form->setRenderer($this->getConfig()->createFormRenderer($this->form));

        $tab = 'Site';
        $this->form->appendField(new Field\Input('site.title'))->setTabGroup($tab)->setLabel('Site Title')->setRequired(true);
        $this->form->appendField(new Field\Input('site.email'))->setTabGroup($tab)->setLabel('Site Email')->setRequired(true);

        $tab = 'SEO';
        $this->form->appendField(new Field\Input('site.meta.keywords'))->setTabGroup($tab)->setLabel('SEO Keywords');
        $this->form->appendField(new Field\Input('site.meta.description'))->setTabGroup($tab)->setLabel('SEO Description');

        $tab = 'Setup';
        $this->form->appendField(new Field\Input('site.google.map.key'))->setTabGroup($tab)->setLabel('Google API Key')
            ->setNotes('<a href="https://cloud.google.com/maps-platform/" target="_blank">Get Google Maps Api Key</a> And be sure to enable `Maps Javascript API`, `Maps Embed API` and `Places API for Web` for this site.');
        $this->form->appendField(new Field\Checkbox('site.client.registration'))->setTabGroup($tab)->setLabel('Client Registration')
            ->setNotes('Enable Client registrations to be submitted');

        $tab = 'Global';
        $this->form->appendField(new Field\Textarea('site.global.css'))->setAttr('id', 'site-global-css')->setTabGroup($tab)->setLabel('Custom Styles')
            ->setNotes('You can omit the &lt;style&gt; tags here')->addCss('code')->setAttr('data-mode', 'css');
        $this->form->appendField(new Field\Textarea('site.global.js'))->setAttr('id', 'site-global-js')->setTabGroup($tab)->setLabel('Custom JS')
            ->setNotes('You can omit the &lt;script&gt; tags here')->addCss('code')->setAttr('data-mode', 'javascript');


        $this->form->appendField(new Event\Submit('update', array($this, 'doSubmit')));
        $this->form->appendField(new Event\Submit('save', array($this, 'doSubmit')));
        $this->form->appendField(new Event\LinkButton('cancel', $this->getBackUrl()));

        $this->form->load($this->data->toArray());
        $this->form->execute();

    }

    /**
     * doSubmit()
     *
     * @param Form $form
     * @param \Tk\Form\Event\Iface $event
     * @throws \Exception
     */
    public function doSubmit($form, $event)
    {
        $values = $form->getValues();
        $this->data->replace($values);
        
        if (empty($values['site.title']) || strlen($values['site.title']) < 3) {
            $form->addFieldError('site.title', 'Please enter your name');
        }
        if (empty($values['site.email']) || !filter_var($values['site.email'], \FILTER_VALIDATE_EMAIL)) {
            $form->addFieldError('site.email', 'Please enter a valid email address');
        }
        
        if ($this->form->hasErrors()) {
            return;
        }
        
        $this->data->save();
        
        \Tk\Alert::addSuccess('Site settings saved.');
        $event->setRedirect($this->getBackUrl());
        if ($form->getTriggeredEvent()->getName() == 'save') {
            $event->setRedirect(\Tk\Uri::create());
        }
    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $this->getActionPanel()->add(\Tk\Ui\Button::create('Plugins', \Bs\Uri::createHomeUrl('/plugins.html'), 'fa fa-plug'));
        $this->getActionPanel()->add(\Tk\Ui\Button::create('Users', \Bs\Uri::createHomeUrl('/userManager.html'), 'fa fa-users'));

        $template = parent::show();
        
        // Render the form
        $template->appendTemplate('form', $this->form->getRenderer()->show());
        return $template;
    }

    /**
     * DomTemplate magic method
     *
     * @return \Dom\Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<HTML
<div>

  <div class="panel panel-default">
    <div class="panel-heading">
      <i class="fa fa-cog"></i> Site Settings
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