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

    public function doByType(mixed $request, string $type): void
    {
        $this->type = $type;
        $this->doDefault();
    }

    public function doDefault(): void
    {
        $this->getPage()->setTitle(ucwords($this->type) . ' Manager');

        if ($this->type == User::TYPE_STAFF) {
            $this->setAccess(Permissions::PERM_MANAGE_STAFF);
        }
        if ($this->type == User::TYPE_MEMBER) {
            $this->setAccess(Permissions::PERM_MANAGE_MEMBERS);
        }

        // init the user table
        $this->table = new \Bs\Table\User();
        $this->table->execute();

        // Set the table rows
        $filter = $this->table->getDbFilter();
        $filter->set('type', $this->type);
        $rows = User::findFiltered($filter);

        $this->table->setRows($rows, Db::getLastStatement()->getTotalRows());
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->appendText('title', $this->getPage()->getTitle());
        $template->setAttr('back', 'href', $this->getBackUrl());

        if ($this->type == User::TYPE_STAFF) {
            $template->setAttr('create-staff', 'href', Uri::create('/user/staffEdit'));
            $template->setVisible('create-staff');
        }
        if ($this->type == User::TYPE_MEMBER) {
            $template->setAttr('create-member', 'href', Uri::create('/user/memberEdit'));
            $template->setVisible('create-member');
        }

        $template->appendTemplate('content', $this->table->show());

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
  <div class="page-actions card mb-3">
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