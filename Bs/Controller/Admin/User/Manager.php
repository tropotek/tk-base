<?php
namespace Bs\Controller\Admin\User;

use Dom\Template;
use Tk\Form\Field;

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

        $this->table = $this->getConfig()->createTable('user-list');
        $this->table->setRenderer($this->getConfig()->createTableRenderer($this->table));

        $actionsCell = $this->getActionsCell();
        $actionsCell->addButton(\Tk\Table\Cell\ActionButton::create('Masquerade', \Tk\Uri::create(),
            'fa fa-user-secret', 'tk-masquerade'))
            ->setOnShow(function ($cell, $obj, $button) {
                /* @var $obj \Bs\Db\User */
                /* @var $button \Tk\Table\Cell\ActionButton */
                if (\Bs\Listener\MasqueradeHandler::canMasqueradeAs(\Bs\Config::getInstance()->getUser(), $obj)) {
                    $button->setUrl(\Tk\Uri::create()->set(\Bs\Listener\MasqueradeHandler::MSQ, $obj->getHash()));
                } else {
                    $button->setAttr('disabled', 'disabled')->addCss('disabled');
                }
            });

        $this->table->appendCell(new \Tk\Table\Cell\Checkbox('id'));
        $this->table->appendCell($actionsCell);
        $this->table->appendCell(new \Tk\Table\Cell\Text('name'))->addCss('key')->setUrl(\Bs\Uri::createHomeUrl('/'.$this->targetRole.'Edit.html'));
        $this->table->appendCell(new \Tk\Table\Cell\Text('username'));
        $this->table->appendCell(new \Tk\Table\Cell\Text('email'));
        $this->table->appendCell(new \Tk\Table\Cell\Text('roleId'))->setOnPropertyValue(function ($cell, $obj, $value) {
            /** @var \Bs\Db\User $obj */
            if ($obj->getRole())
                $value = $obj->getRole()->getName();
            return $value;
        });
        $this->table->appendCell(new \Tk\Table\Cell\Boolean('active'));
        $this->table->appendCell(new \Tk\Table\Cell\Date('created'));

        // Filters
        $this->table->appendFilter(new Field\Input('keywords'))->setLabel('')->setAttr('placeholder', 'Keywords');

        // Actions
        $this->table->appendAction(\Tk\Table\Action\Csv::create());
        $this->table->appendAction(\Tk\Table\Action\Delete::create()->setExcludeIdList(array(1)));

        $filter = $this->table->getFilterValues();

        $users = $this->getConfig()->getUserMapper()->findFiltered($filter, $this->table->getTool('a.name'));
        $this->table->setList($users);

    }

    /**
     * @return \Dom\Template
     * @throws \Dom\Exception
     */
    public function show()
    {
        $template = parent::show();

        $this->getActionPanel()->add(\Tk\Ui\Button::create('Add User', \Bs\Uri::createHomeUrl('/'.$this->targetRole.'Edit.html'), 'fa fa-user'));

        $template->appendTemplate('table', $this->table->getRenderer()->show());
        
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
      <i class="fa fa-users fa-fw"></i> Users
    </div>
    <div class="panel-body">
      <div var="table"></div>
    </div>
  </div>

</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }


}