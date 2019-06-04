<?php
namespace Bs\Controller\Role;

use Tk\Form\Event;
use Tk\Form\Field;
use Tk\ObjectUtil;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 * @deprecated Still Under Construction
 */
class Edit extends \Bs\Controller\AdminEditIface
{


    /**
     * @var null|\Bs\Db\Role
     */
    protected $role = null;


    /**
     * Edit constructor.
     */
    public function __construct()
    {
        $this->setPageTitle('Role Edit');
    }
    
    /**
     * @param \Tk\Request $request
     * @throws \Exception
     */
    public function doDefault(\Tk\Request $request)
    {

        $this->role = $this->getConfig()->createRole();
        $this->role->type = $request->get('type');
        if ($request->get('roleId')) {
            $this->role = $this->getConfig()->getRoleMapper()->find($request->get('roleId'));
        }
        $this->setPageTitle(ucfirst($this->role->getType()) . ' Role Edit');


        $this->setForm(\Bs\Form\Role::create()->setModel($this->role));
        $this->initForm($request);
        $this->getForm()->execute();
        if (!$this->getForm()->isSubmitted() && $this->role->isStatic()) {
            \Tk\Alert::addWarning('You are editing a static ROLE. These roles are set by the system and cannot be modified.');
        }

    }

    public function initForm(\Tk\Request $request) { }

    /**
     * @return \Dom\Template
     * @throws \Exception
     */
    public function show()
    {
        $template = parent::show();

        // Render the form
        $template->appendTemplate('panel', $this->getForm()->show());

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
<div class="">

  <div class="tk-panel" data-panel-title="Role Edit" data-panel-icon="fa fa-id-badge" var="panel"></div>
  
</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }

}