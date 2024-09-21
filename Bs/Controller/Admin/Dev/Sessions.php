<?php
namespace Bs\Controller\Admin\Dev;

use Au\Auth;
use Bs\ControllerAdmin;
use Bs\Db\Permissions;
use Bs\Table;
use Bs\Ui\Crumbs;
use Au\Masquerade;
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
        $this->setAccess(Auth::PERM_ADMIN);

        $this->table = new Table('sessions');
        $this->table->removeAction('__reset');

        $this->table->appendCell('authId')
            ->addCss('text-center');
        $this->table->appendCell('username');
        $this->table->appendCell('name')
            ->addCss('text-nowrap');
        $this->table->appendCell('breadcrumbs')
            ->addCss('max-width text-nowrap');
        $this->table->appendCell('ip')
            ->addCss('text-nowrap');
        // $this->table->appendCell('sessionId')
        //     ->addCss('text-nowrap');
        // $this->table->appendCell('agent')
        //     ->addCss('text-nowrap');
        $this->table->appendCell('type')
            ->addCss('text-nowrap');
        $this->table->appendCell('activity')
            ->addCss('text-nowrap');
        $this->table->appendCell('lifetime')
            ->addCss('text-nowrap');
        $this->table->appendCell('expires')
            ->addHeaderCss('text-center')
            ->addCss('text-nowrap')
            ->addOnValue('\Tk\Table\Type\DateTime::onValue');


        // TODO: add a filter for public/user sessions

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
        $sessions = Db::query("SELECT * FROM _session ORDER BY modified DESC");
        $rows = [];

        foreach ($sessions as $ses) {
            // decode session data
            session_unset();
            session_decode($ses->data);

            $username = $_SESSION[SessionStorage::$SID_USER] ?? '';
            $auth = Auth::findByUsername($username);

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
            $difCreated = $now->diff($created);
            $difLast = $now->diff($modified);

            $authId = 0;
            $type = 'public';
            $name = 'N/A';
            $username = sprintf('<span class="text-muted">%s</span>', $_SESSION['_session.id'] ?? '');

            if ($auth) {
                $authId = $auth->authId;
                if (isset($auth->getDbModel()->type)) {
                    $type = $auth->getDbModel()->type;
                }
                $username = $auth->username;
                if (isset($auth->getDbModel()->nameShort)) {
                    $name = $auth->getDbModel()->nameShort;
                }
                if (Masquerade::isMasquerading()) {
                    $msq = Masquerade::getMasqueradingUser();
                    $username = sprintf('%s<br><small class="text-info" title="Masquerading User">[%s]</small>', $msq->username, $auth->username);
                }

                if ($auth->sessionId == ($_SESSION['_session.id'] ?? '')) {
                    $username = sprintf('<strong>%s</strong>', $username);
                }
            }

            $rows[] = (object)[
                'authId'      => $authId,
                'username'    => $username,
                'ip'          => $_SESSION[Session::SID_IP] ?? '',
                'agent'       => $_SESSION[Session::SID_AGENT] ?? '',
                'sessionId'   => $_SESSION['_session.id'] ?? '',
                'type'        => $type,
                'name'        => $name,
                'breadcrumbs' => $breadcrumbs,
                'lifetime'    => $difCreated->format('%H:%i:%S'),
                'activity'    => $difLast->format('%H:%i:%S'),
                'expires'     => Date::create($ses->expiry),
                'isPublic'    => is_object($auth),
            ];
        }
        // reset back to original user session
        session_reset();
        // todo: sort user sessions first

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