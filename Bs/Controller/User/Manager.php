<?php
namespace Bs\Controller\User;

use Bs\ControllerAdmin;
use Bs\Db\Permissions;
use Bs\Db\User;
use Bs\Table;
use Dom\Template;
use Tk\Uri;
use Tt\Db;

class Manager extends ControllerAdmin
{

    protected ?Table $table = null;
    protected string $type  = '';

//    public function doByType(mixed $request, string $type): void
//    {
//        $this->type = $type;
//        $this->doDefault();
//    }

    public function doDefault(): void
    {
        $this->getPage()->setTitle('User Manager');
        $this->getCrumbs()->reset();

        $this->setAccess(Permissions::ACCESS_EDIT_USERS);

        $this->getPage()->setTitle('User Manager');

        $this->type = match(true) {
            $this->getAuthUser()->hasPermission(Permissions::ACCESS_EDIT_USERS) => '',
            $this->getAuthUser()->hasPermission(Permissions::PERM_MANAGE_STAFF) => User::TYPE_STAFF,
            $this->getAuthUser()->hasPermission(Permissions::PERM_MANAGE_MEMBERS) => User::TYPE_MEMBER,
            default => ''
        };

        // init the user table
        $this->table = new \Bs\Table\User();
        $this->table->execute();

        // Set the table rows
        $filter = $this->table->getDbFilter();

        if ($this->type) {
            $filter->set('type', $this->type);
        }
        $rows = User::findFiltered($filter);

        $this->table->setRows($rows, Db::getLastStatement()->getTotalRows());
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->appendText('title', $this->getPage()->getTitle());
        $template->setAttr('back', 'href', $this->getBackUrl());

        $template->setAttr('create-staff', 'href', Uri::create('/user/staffEdit'));
        $template->setAttr('create-member', 'href', Uri::create('/user/memberEdit'));
        if ($this->getAuthUser()->hasPermission(Permissions::PERM_MANAGE_STAFF)) {
            $template->setVisible('create-member');
        }
        if ($this->getAuthUser()->hasPermission(Permissions::PERM_MANAGE_MEMBERS)) {
            $template->setVisible('create-staff');
        }

        $template->appendTemplate('content', $this->table->show());

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
  <div class="card mb-3">
    <div class="card-header"><i class="fa fa-cogs"></i> Actions</div>
    <div class="card-body" var="actions">
      <a href="/" title="Back" class="btn btn-outline-secondary" var="back"><i class="fa fa-arrow-left"></i> Back</a>
      <a href="/" title="Create Staff" class="btn btn-outline-secondary" choice="create-staff"><i class="fa fa-user"></i> Create Staff</a>
      <a href="/" title="Create Member" class="btn btn-outline-secondary" choice="create-member"><i class="fa fa-user"></i> Create Member</a>
    </div>
  </div>
  <div class="card mb-3">
    <div class="card-header" var="title"><i class="fa fa-users"></i> </div>
    <div class="card-body" var="content"></div>
  </div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}