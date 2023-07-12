<?php
namespace Bs\Controller\User;

use Bs\Db\User;
use Bs\PageController;
use Bs\Table\ManagerTrait;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Uri;

class Manager extends PageController
{
    use ManagerTrait;

    protected string $type = '';

    public function __construct()
    {
        parent::__construct($this->getFactory()->getPublicPage());
        $this->getPage()->setTitle('User Manager');
        $this->getCrumbs()->reset();
    }

    public function doByType(Request $request, string $type)
    {
        $this->type = $type;
        return $this->doDefault($request);
    }

    public function doDefault(Request $request)
    {
        if ($this->type == User::TYPE_MEMBER) {
            $this->setAccess(User::PERM_MANAGE_MEMBER);
        } else if ($this->type == User::TYPE_STAFF) {
            $this->setAccess(User::PERM_MANAGE_STAFF);
        } else {
            $this->setAccess(User::PERM_ADMIN);
        }

        $this->getPage()->setTitle(ucfirst($this->type ?: 'User') . ' Manager');

        // Get the form template
        $this->setTable(new \Bs\Table\User());
        $this->getTable()->setType($this->type);
        $filter = [];
        if ($this->type) {
            $filter['type'] = $this->type;
        }
        $this->getTable()->findList($filter, $this->getTable()->getTool('name'));
        $this->getTable()->execute($request);

        return $this->getPage();
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->appendText('title', $this->getPage()->getTitle());
        $template->setAttr('create-staff', 'href', Uri::create('/user/staffEdit'));
        $template->setAttr('create-member', 'href', Uri::create('/user/memberEdit'));
        if ($this->type == User::TYPE_STAFF) {
            $template->setVisible('create-member', false);
        }
        if ($this->type == User::TYPE_MEMBER) {
            $template->setVisible('create-staff', false);
        }

        $template->appendTemplate('content', $this->getTable()->show());

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