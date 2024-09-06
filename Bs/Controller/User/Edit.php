<?php
namespace Bs\Controller\User;

use Bs\ControllerAdmin;
use Bs\Db\Permissions;
use Bs\Db\User;
use Dom\Template;
use Tk\Alert;
use Tk\Exception;
use Tk\Uri;

class Edit extends ControllerAdmin
{
    protected ?User          $user = null;
    protected ?\Bs\Form\User $form = null;
    protected string         $type = User::TYPE_MEMBER;


    public function doDefault(mixed $request, string $type): void
    {
        $this->getPage()->setTitle('Edit ' . ucfirst($type));

        $userId  = intval($_GET['userId'] ?? 0);
        $newType = trim($_GET['cv'] ?? '');

        $this->type = $type;
        $this->user = User::create();
        $this->getUser()->type = $type;
        if ($userId) {
            $this->user = User::find($userId);
            if (!$this->getUser()) {
                throw new Exception('Invalid User ID: ' . $userId);
            }
        }

        if ($this->type == User::TYPE_STAFF) {
            $this->setAccess(Permissions::PERM_MANAGE_STAFF);
        }
        if ($this->type == User::TYPE_MEMBER) {
            $this->setAccess(Permissions::PERM_MANAGE_MEMBERS);
        }

        $this->form = new \Bs\Form\User($this->getUser());
        $this->form->setType($this->type);
        $this->form->execute($_POST);

        if ($this->getAuthUser()->hasPermission(Permissions::PERM_ADMIN) && !empty($newType)) {
            if ($newType == User::TYPE_STAFF) {
                $this->getUser()->type = User::TYPE_STAFF;
                Alert::addSuccess('User now set to type STAFF, please select and save the users new permissions.');
            } else if ($newType == User::TYPE_MEMBER) {
                $this->getUser()->type = User::TYPE_MEMBER;
                Alert::addSuccess('User now set to type MEMBER.');
            }
            $this->getUser()->save();
            Uri::create()->remove('cv')->redirect();
        }

    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setAttr('back', 'href', $this->getBackUrl());

        if ($this->getAuthUser()->hasPermission(Permissions::PERM_ADMIN)) {
            if ($this->getUser()->isType(User::TYPE_MEMBER)) {
                $url = Uri::create()->set('cv', User::TYPE_STAFF);
                $template->setAttr('to-staff', 'href', $url);
                $template->setVisible('to-staff');
            } else if ($this->getUser()->isType(User::TYPE_STAFF)) {
                $url = Uri::create()->set('cv', User::TYPE_MEMBER);
                $template->setAttr('to-member', 'href', $url);
                $template->setVisible('to-member');
            }
        }

        $template->appendText('title', $this->getPage()->getTitle());
        $template->appendTemplate('content', $this->form->show());

        if (!$this->getUser()->userId) {
            $template->setVisible('new-user');
        }

        return $template;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
  <div class="page-actions card mb-3">
    <div class="card-header"><i class="fa fa-cogs"></i> Actions</div>
    <div class="card-body" var="actions">
      <a href="" title="Back" class="btn btn-outline-secondary" var="back"><i class="fa fa-arrow-left"></i> Back</a>
      <a href="/" title="Convert user to staff" data-confirm="Convert this user to staff" class="btn btn-outline-secondary" choice="to-staff"><i class="fa fa-retweet"></i> Convert To Staff</a>
      <a href="/" title="Convert user to member" data-confirm="Convert this user to member" class="btn btn-outline-secondary" choice="to-member"><i class="fa fa-retweet"></i> Convert To Member</a>
    </div>
  </div>
  <div class="card mb-3">
    <div class="card-header" var="title"><i class="fa fa-users"></i> </div>
    <div class="card-body" var="content">
      <p choice="new-user"><b>NOTE:</b> New users will be sent an email requesting them to activate their account and create a new password.</p>
    </div>
  </div>
</div>
HTML;
        return $this->loadTemplate($html);
    }


}