<?php
namespace Bs\Controller\Admin\Dev;

use Bs\Auth;
use Bs\Mvc\ControllerAdmin;
use Bs\Mvc\Table;
use Bs\Ui\Breadcrumbs;
use Bs\Db\Masquerade;
use Dom\Template;
use Tk\Auth\Storage\SessionStorage;
use Tk\Date;
use Tk\Db;
use Tk\Session;
use Tk\Form\Field\Input;
use Tk\Table\Action\ColumnSelect;

class Sessions extends ControllerAdmin
{
    protected Table $table;

    protected int $totalPublic = 0;
    protected int $totalPrivate = 0;

    public function doDefault(): void
    {
        $this->getPage()->setTitle('Current Sessions', 'fa fa-server');
        $this->setUserAccess(Auth::PERM_ADMIN);

        $this->table = new Table('sessions');
        $this->table->removeAction('__reset');

        $this->table->appendCell('authId')
            ->addCss('text-center');

        $this->table->appendCell('username')
            ->addCss('text-nowrap');

        $this->table->appendCell('name')
            ->addCss('text-nowrap');

        $this->table->appendCell('breadcrumbs')
            ->addCss('max-width text-nowrap');

        $this->table->appendCell('ip')
            ->addCss('text-nowrap');

        $this->table->appendCell('type')
            ->addCss('text-nowrap');

        $this->table->appendCell('sso')
            ->setHeader('SSO')
            ->addCss('text-nowrap');

        $this->table->appendCell('activity')
            ->setHeaderAttr('title', 'Activity: HH::MM:SS')
            ->addCss('text-nowrap');

        $this->table->appendCell('duration')
            ->setHeaderAttr('title', 'Duration: HH::MM:SS')
            ->addCss('text-nowrap');

        $this->table->appendCell('expires')
            ->setHeaderAttr('title', 'Expires: HH::MM:SS')
            ->setHeader('Expires In')
            ->addHeaderCss('text-center')
            ->addCss('text-nowrap');


        // Add Filter Fields
//        $this->table->getForm()->appendField(new Input('search'))
//            ->setAttr('placeholder', 'Search');

        $list = ['' => '-- All --', 'pub' => 'Public', 'prv' => 'Private'];
        $this->table->getForm()->appendField(new \Tk\Form\Field\Select('scope', $list))->setValue('prv');

        // TODO: add a filter for public/user sessions, and csv export
        $this->table->appendAction(ColumnSelect::create());

        // execute actions and set table orderBy from request
        $this->table->execute();

        $rows = $this->getSessions($this->table->getDbFilter());

        $this->table->setRows($rows);
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->appendText('title', $this->getPage()->getTitle());
        $template->addCss('icon', $this->getPage()->getIcon());

        $template->setText('totalPublic', strval($this->totalPublic));
        $template->setText('totalPrivate', strval($this->totalPrivate));

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

    protected function getSessions(Db\Filter $filter): array
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
                if ($itm instanceof Breadcrumbs) {
                    $crumbs = array_combine($itm->getCrumbStack(), $itm->getTitleStack());
                    $breadcrumbs = '<ol class="breadcrumb">';
                    foreach ($crumbs as $url => $title) {
                        $breadcrumbs .= sprintf('<li class="breadcrumb-item"><a href="%s">%s</a>', $url, $title);
                    }
                    $breadcrumbs .= '</ol>';
                    break;
                }
            }

            $now = Date::create();
            $created = Date::create($ses->created);
            $modified = Date::create($ses->modified);
            $expiry = Date::create($ses->expiry);
            $difCreated = $now->diff($created);
            $difLast = $now->diff($modified);
            $expiresIn = $now->diff($expiry);

            $authId = 0;
            $type = 'public';
            $name = 'N/A';
            $username = sprintf('<span class="text-muted">%s</span>', $_SESSION['_session.id'] ?? '');

            if ($auth) {
                $authId = $auth->authId;
                $type = 'private';
                $username = $auth->username;
                if (isset($auth->getDbModel()->nameShort)) {
                    $name = $auth->getDbModel()->nameShort;
                }
                if (Masquerade::isMasquerading()) {
                    $msq = Masquerade::getMasqueradingUser();
                    $username = sprintf('%s <span class="text-muted">[%s]</span>', $auth->username, $msq->username);
                }

                if ($auth->sessionId == ($_SESSION['_session.id'] ?? '')) {
                    $username = sprintf('<strong>%s</strong>', $username);
                }
                $this->totalPrivate++;
            } else {
                $this->totalPublic++;
            }

            $scope = $filter->get('scope');
            if (!empty($scope)) {
                if ($scope == 'pub' && $auth instanceof Auth) {
                    continue;
                }
                if ($scope == 'prv' && !($auth instanceof Auth)) {
                    continue;
                }
            }

            $rows[] = (object)[
                'authId'      => $authId,
                'username'    => $username,
                'ip'          => $_SESSION[Session::SID_IP] ?? '',
                'agent'       => $_SESSION[Session::SID_AGENT] ?? '',
                'sessionId'   => $ses->session_id,
                'sso'         => $_SESSION['_OAUTH'] ?? '',
                'type'        => $type,
                'name'        => $name,
                'breadcrumbs' => $breadcrumbs,
                'duration'    => $difCreated->format('%H:%I:%S'),
                'activity'    => $difLast->format('%H:%I:%S'),
                'expires'     => $expiresIn->format('%H:%I:%S'),
                'isPublic'    => is_object($auth),
            ];
        }
        // reset back to original user session
        session_reset();

        return $rows;
    }


    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div class="card mb-3">
    <div class="card-header"><i var="icon"></i> <span var="title"></span></div>
    <div class="card-body" var="content">
        <p>Current user sessions:</p>
        <ul>
           <li>Private Sessions: <span var="totalPrivate"></span></li>
           <li>Public Sessions: <span var="totalPublic"></span></li>
        </ul>
    </div>
</div>
HTML;
        return $this->loadTemplate($html);
    }
}