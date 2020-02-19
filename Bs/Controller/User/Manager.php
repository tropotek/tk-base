<?php
namespace Bs\Controller\User;

use Bs\Db\User;
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
    protected $targetType = '';


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
     * @param string $targetType
     * @throws \Exception
     */
    public function doDefaultType(\Tk\Request $request, $targetType)
    {
        $this->targetType = $targetType;
        $this->doDefault($request);
    }

    /**
     * @param \Tk\Request $request
     * @throws \Exception
     */
    public function doDefault(\Tk\Request $request)
    {
        switch($this->targetType) {
            case User::TYPE_ADMIN:
                $this->setPageTitle('Admin Users');
                break;
            case User::TYPE_MEMBER:
                $this->setPageTitle('Member Manager');
                break;
        }
        $editUrl = \Bs\Uri::createHomeUrl('/'.$this->targetType.'Edit.html');

        $this->table = \Bs\Table\User::create()->setEditUrl($editUrl)->init();
        $this->table->setList($this->table->findList(array('type' => $this->targetType)));

    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $this->getActionPanel()->append(\Tk\Ui\Link::createBtn('Add User',
            \Bs\Uri::createHomeUrl('/'.$this->targetType.'Edit.html'), 'fa fa-user'));
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