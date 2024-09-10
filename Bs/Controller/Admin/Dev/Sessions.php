<?php
namespace Bs\Controller\Admin\Dev;

use Bs\ControllerAdmin;
use Bs\Db\Permissions;
use Bs\Db\User;
use Bs\Table;
use Bs\Ui\Crumbs;
use Dom\Template;
use Tk\Date;
use Tk\Db;


class Sessions extends ControllerAdmin
{
    protected Table $table;

    public function doDefault(): void
    {
        $this->getPage()->setTitle('Current Sessions');
        $this->setAccess(Permissions::PERM_ADMIN);

        $this->table = new Table('sessions');

        $this->table->appendCell('userId')
            ->addCss('text-center');
        $this->table->appendCell('username')
            ->addHeaderCss('max-width');
        $this->table->appendCell('name')
            ->addCss('text-nowrap');
        $this->table->appendCell('ip')
            ->addCss('text-nowrap');
        $this->table->appendCell('type')
            ->addCss('text-nowrap');
        $this->table->appendCell('breadcrumbs')
            ->addCss('text-nowrap');
        $this->table->appendCell('duration')
            ->addCss('text-nowrap');
        $this->table->appendCell('expires')
            ->addHeaderCss('text-center')
            ->addCss('text-nowrap')
            ->addOnValue('\Tk\Table\Type\DateTime::onValue');

        // execute actions and set table orderBy from request
        $this->table->execute();

        $rows = $this->getSessions();

        $this->table->setRows($rows);
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setAttr('back', 'href', $this->getBackUrl());

        $this->table->getRenderer()->setFooterEnabled(false);
        $this->table->addCss('table-hover');
        $template->appendTemplate('content', $this->table->show());

        return $template;
    }

    protected function getSessions(): array
    {
        $sessions = Db::query("SELECT * FROM _session");
        $rows = [];
        $my_sess = $_SESSION;

        foreach ($sessions as $ses) {
            // decode session data
            session_decode($ses->data);
            $data = $_SESSION;

            $username = $data['_auth.session'] ?? '';
            $user = User::findByUsername($username);
            if (!$user) continue;

            $breadcrumbs = '';
            foreach ($data as $itm) {
                if ($itm instanceof Crumbs) {
                    $itm->setShowActiveUrl(true);
                    $breadcrumbs = $itm->show()->setAttr('crumbs', 'style', 'width: max-content;margin-bottom: 0;')->toString();
                    $itm->setShowActiveUrl(false);
                    break;
                }
            }

            $created = Date::create($ses->created);
            $time = Date::create($ses->modified);
            $dif = $time->diff($created);

            $rows[] = (object)[
                'userId' => $user->userId,
                'username' => $user->username,
                'name' => $user->getName(),
                'ip' => '0.0.0.0',
                'type' => $user->type,
                'breadcrumbs' => $breadcrumbs,
                'duration' => $dif->format('%H:%i:%s'),
                'expires' => Date::create($ses->expiry),
            ];
        }

        $_SESSION = $my_sess;  // restore our session
        return $rows;
    }


    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
  <div class="page-actions card mb-3">
    <div class="card-header"><i class="fa fa-cogs"></i> Actions</div>
    <div class="card-body" var="actions">
      <a href="/" title="Back" class="btn btn-outline-secondary" var="back"><i class="fa fa-arrow-left"></i> Back</a>
    </div>
  </div>
  <div class="card mb-3">
    <div class="card-header" var="title"><i class="fa fa-calendar"></i> </div>
    <div class="card-body" var="content"></div>
  </div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}