<?php
namespace Bs\Controller\User;

use Bs\Db\User;
use Bs\Form\EditTrait;
use Bs\PageController;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Alert;
use Tk\Exception;
use Tk\Uri;

class Edit extends PageController
{
    use EditTrait;

    protected ?User $user = null;

    protected string $type = User::TYPE_MEMBER;


    public function __construct()
    {
        parent::__construct($this->getFactory()->getAdminPage());
        $this->getPage()->setTitle('Edit User');
        $this->setAccess(User::PERM_MANAGE_MEMBER | User::PERM_MANAGE_STAFF);
    }

    public function doDefault(Request $request, string $type): \App\Page|\Dom\Mvc\Page
    {
        $this->type = $type;
        $this->user = $this->getFactory()->createUser();
        $this->getUser()->setType($type);

        if ($request->query->getInt('userId')) {
            $this->user = $this->getFactory()->getUserMap()->find($request->query->getInt('userId'));
        }
        if (!$this->getUser()) {
            throw new Exception('Invalid User ID: ' . $request->query->getInt('userId'));
        }

        // Get the form template
        $this->setForm(new \Bs\Form\User($this->getUser()));
        $this->getForm()->init();
        $this->getForm()->setType($this->type);
        $this->getForm()->execute($request->request->all());
//        $this->form = new \Bs\Form\User();
//        $this->form->doDefault($request,  $request->query->getInt('userId', 0), $type);

        if ($request->query->get('cv')) {
            $newType = trim($request->query->get('cv'));
            if ($newType == User::TYPE_STAFF) {
                $this->getUser()->setType(User::TYPE_STAFF);
                Alert::addSuccess('User now set to type STAFF, please select and save the users new permissions.');
            } else if ($newType == User::TYPE_MEMBER) {
                $this->getUser()->setType(User::TYPE_MEMBER);
                Alert::addSuccess('User now set to type MEMBER.');
            }
            $this->getUser()->save();
            Uri::create()->remove('cv')->redirect();
        }

        return $this->getPage();
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setAttr('back', 'href', $this->getBackUrl());

        if ($this->getUser()->getId() > 1 && $this->getAuthUser()->hasPermission(User::PERM_ADMIN)) {
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

        if (!$this->getUser()->getId()) {
            $template->setVisible('new-user');
        }

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
  <div class="card mb-3">
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