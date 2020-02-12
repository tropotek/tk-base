<?php
namespace Bs\Controller\Admin\User;

use Dom\Template;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Manager extends \Bs\Controller\AdminManagerIface
{

    /**
     * Setup the controller to work with users of this role
     * @var string
     */
    protected $targetRole = 'user';


    /**
     * @throws \Exception
     */
    public function __construct()
    {
        $this->setPageTitle('User Manager');
        //$this->getCrumbs()->reset();
    }

    /**
     * @param \Tk\Request $request
     * @param string $targetRole
     * @throws \Exception
     */
    public function doDefaultRole(\Tk\Request $request, $targetRole)
    {
        $this->targetRole = $targetRole;

        $this->doDefault($request);
    }

    /**
     * @param \Tk\Request $request
     * @throws \Exception
     */
    public function doDefault(\Tk\Request $request)
    {
        switch($this->targetRole) {
            case \Bs\Db\Role::TYPE_ADMIN:
                $this->setPageTitle('Admin Users');
                break;
            case \Bs\Db\Role::TYPE_USER:
                $this->setPageTitle('User Manager');
                break;
        }
        $editUrl = \Bs\Uri::createHomeUrl('/'.$this->targetRole.'Edit.html');

        $this->table = \Bs\Table\User::create()->setEditUrl($editUrl)->init();
        $this->table->setList($this->table->findList(array()));

    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $this->getActionPanel()->append(\Tk\Ui\Link::createBtn('Add User', \Bs\Uri::createHomeUrl('/'.$this->targetRole.'Edit.html'), 'fa fa-user'));
        $template = parent::show();

        $template->appendTemplate('table', $this->table->show());
        
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
  <div class="tk-panel" data-panel-icon="fa fa-users" var="table"></div>
</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }


}