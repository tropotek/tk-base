<?php
namespace Bs\Controller\Admin\Dev;

use Bs\ControllerAdmin;
use Bs\Db\Permissions;
use Bs\Db\User;
use Bs\Table;
use Bs\Ui\Crumbs;
use Bs\Util\Masquerade;
use Dom\Template;
use Tk\Auth\Storage\SessionStorage;
use Tk\Date;
use Tk\Db;
use Tk\Db\Session;

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
        // $this->table->appendCell('agent')
        //     ->addCss('text-nowrap');
        $this->table->appendCell('type')
            ->addCss('text-nowrap');
        $this->table->appendCell('breadcrumbs')
            ->addCss('text-nowrap');
        $this->table->appendCell('activity')
            ->addCss('text-nowrap');
        $this->table->appendCell('lifetime')
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

        // todo: figure out how to show all the crumbs?
        $css = <<<CSS
.tk-table td.mBreadcrumbs {
    overflow: hidden;
    white-space: nowrap;
}
.tk-table ol.breadcrumb {
    width: max-content;
    margin-bottom: 0;
}
CSS;
        $template->appendCss($css);

        return $template;
    }

    protected function getSessions(): array
    {
        $sessions = Db::query("SELECT * FROM _session");
        $rows = [];

        foreach ($sessions as $ses) {
            // decode session data
            session_unset();
            session_decode($ses->data);

            $username = $_SESSION[SessionStorage::$SID_USER] ?? '';
            $user = User::findByUsername($username);
            if (!$user) continue;

            $breadcrumbs = '';
            foreach ($_SESSION as $itm) {
                if ($itm instanceof Crumbs) {
                    $itm->setShowActiveUrl(true);
                    $breadcrumbs = $itm->show()->toString();
                    $itm->setShowActiveUrl(false);
                    break;
                }
            }

            $now = Date::create();
            $created = Date::create($ses->created);
            $modified = Date::create($ses->modified);
            $difCreated = $modified->diff($created);
            $difLast = $now->diff($modified);
            $username = $user->username;
            $name = $user->getName();
            if (Masquerade::isMasquerading()) {
                $msq = Masquerade::getMasqueradingUser();
                $name = $msq->getName();
                $username = sprintf('%s<br><small class="text-info" title="Masquerading User">[%s - %s]</small>', $msq->username, $user->username, $user->getName());
            }

            $rows[] = (object)[
                'userId' => $user->userId,
                'username' => $username,
                'name' => $name,
                'ip' => $_SESSION[Session::SID_IP] ?? '',
                'agent' => $_SESSION[Session::SID_AGENT] ?? '',
                'type' => $user->type,
                'breadcrumbs' => $breadcrumbs,
                'lifetime' => $difCreated->format('%H:%i:%S'),
                'activity' => $difLast->format('%H:%i:%S'),
                'expires' => Date::create($ses->expiry),
            ];
        }
        // reset back to original user session
        session_reset();
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