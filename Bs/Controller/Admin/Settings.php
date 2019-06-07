<?php
namespace Bs\Controller\Admin;



/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Settings extends \Bs\Controller\AdminEditIface
{

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
     * @param \Tk\Request $request
     * @return void
     * @throws \Exception
     */
    public function doDefault(\Tk\Request $request)
    {
        $this->data = \Tk\Db\Data::create();

        $this->init();
        $this->getForm()->execute();

    }

    /**
     * init the form and other stuff before form->execute()
     */
    public function init()
    {
        $this->setForm(\Bs\Form\Settings::create()->setModel($this->data));
    }

    /**
     * init the action panel
     */
    public function initActionPanel()
    {
        $this->getActionPanel()->append(\Tk\Ui\Link::createBtn('Plugins',
            \Bs\Uri::createHomeUrl('/plugins.html'), 'fa fa-plug'));
        $this->getActionPanel()->append(\Tk\Ui\Link::createBtn('Users',
            \Bs\Uri::createHomeUrl('/userManager.html'), 'fa fa-users'));
        if ($this->getConfig()->isDebug()) {
            $this->getActionPanel()->append(\Tk\Ui\Link::createBtn('Roles {D}',
                \Bs\Uri::createHomeUrl('/roleManager.html'), 'fa fa-group'));
        }
    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $this->initActionPanel();
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
<div class="tk-panel" data-panel-icon="fa fa-cogs" var="form"></div>
HTML;

        return \Dom\Loader::load($xhtml);
    }
}