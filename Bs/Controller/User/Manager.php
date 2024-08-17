<?php
namespace Bs\Controller\User;

use Bs\ControllerDomInterface;
use Bs\Db\Permissions;
use Bs\Db\User;
use Bs\Table;
use Bs\Table\ManagerTrait;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Uri;
use Tt\Db;

class Manager extends ControllerDomInterface
{

    protected ?Table $table = null;
    protected string $type  = '';

    public function doByType(Request $request, string $type): void
    {
        $this->type = $type;
        $this->doDefault($request);
    }

    public function doDefault(Request $request): void
    {
        $this->getPage()->setTitle('User Manager');
        $this->getCrumbs()->reset();

        if ($this->type == User::TYPE_MEMBER) {
            $this->setAccess(Permissions::PERM_MANAGE_MEMBER);
        } else if ($this->type == User::TYPE_STAFF) {
            $this->setAccess(Permissions::PERM_MANAGE_STAFF);
        } else {
            $this->setAccess(Permissions::PERM_ADMIN);
        }

        $this->getPage()->setTitle(ucfirst($this->type ?: 'User') . ' Manager');

        // init the user table
        $this->table = new \Bs\Table\User();
        $this->table->setType($this->type);
        $this->table->execute($request);

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
        if ($this->type == User::TYPE_STAFF) {
            $template->setVisible('create-member', false);
        }
        if ($this->type == User::TYPE_MEMBER) {
            $template->setVisible('create-staff', false);
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
      <a href="/" title="Create Staff" class="btn btn-outline-secondary" var="create-staff"><i class="fa fa-user"></i> Create Staff</a>
      <a href="/" title="Create Member" class="btn btn-outline-secondary" var="create-member"><i class="fa fa-user"></i> Create Member</a>
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