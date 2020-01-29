<?php
namespace Bs\Controller\Role;

use Dom\Template;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 * @deprecated Still Under Construction
 */
class Manager extends \Bs\Controller\AdminManagerIface
{


    /**
     *
     */
    public function __construct()
    {
        $this->setPageTitle('Role Manager');

    }

    /**
     * @param \Tk\Request $request
     * @throws \Exception
     */
    public function doDefault(\Tk\Request $request)
    {
        $this->setPageTitle('Role Manager');

        $this->setTable(\Bs\Table\Role::create()->setEditUrl(\Bs\Uri::createHomeUrl('/roleEdit.html'))->init());

        $filter = array();
//        $filter['institutionId'] = $this->getConfig()->getInstitutionId();
//        if ($this->getAuthUser()->isStudent() || $this->getAuthUser()->isStaff())
//            $filter['userId'] = $this->getAuthUser()->getId();

        $this->getTable()->setList($this->getTable()->findList($filter));

    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $btn = \Tk\Ui\ButtonDropdown::createButtonDropdown('Add Role', 'fa fa-id-badge', array(
            \Tk\Ui\Link::create('Admin Role', \Bs\Uri::createHomeUrl('/roleEdit.html')->set('type', \Bs\Db\Role::TYPE_ADMIN))
            //,\Tk\Ui\Link::create('User Role', \Bs\Uri::createHomeUrl('/roleEdit.html')->set('type', \Bs\Db\Role::TYPE_USER))
        ));
        $this->getActionPanel()->append($btn);

        $template = parent::show();

        $template->appendTemplate('panel', $this->table->getRenderer()->show());

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
<div class="">

  <div class="tk-panel" data-panel-title="Role" data-panel-icon="fa fa-id-badge" var="panel"></div>
    
</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }


}