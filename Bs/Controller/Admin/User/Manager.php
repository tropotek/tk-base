<?php
namespace Bs\Controller\Admin\User;

use Tk\Request;
use Dom\Template;
use Tk\Form\Field;

/**
 *
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Manager extends \Bs\Controller\AdminManagerIface
{


    /**
     * @throws \Exception
     */
    public function __construct()
    {
        $this->setPageTitle('User Manager');
        $this->getCrumbs()->reset();
    }

    /**
     *
     * @param Request $request
     * @throws \Tk\Db\Exception
     * @throws \Tk\Exception
     * @throws \Tk\Form\Exception
     * @throws \Exception
     */
    public function doDefault(Request $request)
    {
        $this->table = $this->getConfig()->createTable('user-list');
        $this->table->setRenderer($this->getConfig()->createTableRenderer($this->table));

        $actionsCell = $this->getActionsCell();
        $actionsCell->addButton(\Tk\Table\Cell\ActionButton::create('Masquerade', \Tk\Uri::create(),
            'fa  fa-user-secret', 'tk-masquerade'))
            ->setOnShow(function ($cell, $obj, $button) {
                /* @var $obj \Bs\Db\User */
                /* @var $button \Tk\Table\Cell\ActionButton */
                if (\Bs\Listener\MasqueradeHandler::canMasqueradeAs(\Bs\Config::getInstance()->getUser(), $obj)) {
                    $button->setUrl(\Tk\Uri::create()->set(\Bs\Listener\MasqueradeHandler::MSQ, $obj->getId()));
                } else {
                    $button->setAttr('disabled', 'disabled')->addCss('disabled');
                }
            });

        $this->table->addCell(new \Tk\Table\Cell\Checkbox('id'));
        $this->table->addCell($actionsCell);
        $this->table->addCell(new \Tk\Table\Cell\Text('name'))->addCss('key')->setUrl(\Tk\Uri::create('admin/userEdit.html'));
        $this->table->addCell(new \Tk\Table\Cell\Text('username'));
        $this->table->addCell(new \Tk\Table\Cell\Text('email'));
        $this->table->addCell(new \Tk\Table\Cell\Text('role'));
        $this->table->addCell(new \Tk\Table\Cell\Boolean('active'));
        $this->table->addCell(new \Tk\Table\Cell\Date('created'));

        // Filters
        $this->table->addFilter(new Field\Input('keywords'))->setLabel('')->setAttr('placeholder', 'Keywords');

        // Actions
        $this->table->addAction(new \Tk\Table\Action\Csv($this->getConfig()->getDb()));
        $this->table->addAction(new \Tk\Table\Action\Delete())->setExcludeList(array(1));

        $users = $this->getConfig()->getUserMapper()->findFiltered($this->table->getFilterValues(), $this->table->getTool('a.name'));
        $this->table->setList($users);

    }

    /**
     * @return \Dom\Template
     * @throws \Dom\Exception
     */
    public function show()
    {
        $template = parent::show();

        $this->getActionPanel()->add(\Tk\Ui\Button::create('Add User', \Tk\Uri::create('/admin/userEdit.html'), 'fa fa-user'));

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