<?php
namespace Bs\Table;

use Bs\Db\Permissions;
use Bs\Table;
use Bs\Util\Masquerade;
use Tk\Alert;
use Tk\Form\Field\Input;
use Tk\Form\Field\Select;
use Tk\Uri;
use Tt\Table\Action\Csv;
use Tt\Table\Cell;
use Tt\Table\Cell\RowSelect;

class User extends Table
{
    protected string $type = '';


    public function init(): static
    {
        $rowSelect = RowSelect::create('id', 'userId');
        $this->appendCell($rowSelect);

        $this->appendCell('actions')
            ->addCss('text-nowrap text-center')
            ->addOnValue(function(\Bs\Db\User $user, Cell $cell) {
                $msq  = Uri::create()->set(Masquerade::QUERY_MSQ, $user->userId);
                return <<<HTML
                    <a class="btn btn-outline-dark" href="$msq" title="Masquerade" data-confirm="Are you sure you want to log-in as user {$user->getName()}"><i class="fa fa-fw fa-user-secret"></i></a>
                HTML;
            });

        $this->appendCell('username')
            ->addCss('text-nowrap')
            ->addHeaderCss('max-width')
            ->setSortable(true)
            ->addOnValue(function(\Bs\Db\User $user, Cell $cell) {
                $url = Uri::create('/user/'.$user->type.'Edit', ['userId' => $user->userId]);
                return sprintf('<a href="%s">%s</a>', $url, $user->username);
            });

        $this->appendCell('nameFirst')
            ->addCss('text-nowrap')
            ->setSortable(true);

        $this->appendCell('nameLast')
            ->addCss('text-nowrap')
            ->setSortable(true);

        $this->appendCell('email')
            ->setSortable(true)
            ->addOnValue(function(\Bs\Db\User $user, Cell $cell) {
                return sprintf('<a href="mailto:%s">%s</a>', $user->email, $user->email);
            });

        if ($this->getAuthUser()->hasPermission(Permissions::PERM_ADMIN)) {
            $this->appendCell('permissions')
                ->addOnValue(function (\Bs\Db\User $user, Cell $cell) {
                    if ($user->hasPermission(Permissions::PERM_ADMIN)) {
                        $list = $user->getAvailablePermissions();
                        return $list[Permissions::PERM_ADMIN];
                    }
                    $list = array_filter($user->getAvailablePermissions(), function ($k) use ($user) {
                        return $user->hasPermission($k);
                    }, ARRAY_FILTER_USE_KEY);
                    return implode(', <br/>', $list);
                });
        }

        $this->appendCell('active')
            ->setSortable(true)
            ->addOnValue('\Tt\Table\Type\Boolean::onValue');

        $this->appendCell('lastLogin')
            ->addCss('text-nowrap')
            ->setSortable(true)
            ->addOnValue('\Tt\Table\Type\DateTime::onValue');

        $this->appendCell('created')
            ->addCss('text-nowrap')
            ->setSortable(true)
            ->addOnValue('\Tt\Table\Type\DateFmt::onValue');


        // Add Filter Fields
        $this->getForm()->appendField(new Input('search'))
            ->setAttr('placeholder', 'Search: uid, name, email, username');

        $list = ['-- All Users --' => '', 'Active' => 'y', 'Disabled' => 'n'];
        $this->getForm()->appendField(new Select('active', $list))->setValue('y');

        // init filter fields for actions to access to the filter values
        $this->initForm();

        // Add Table actions
        $this->appendAction(\Tt\Table\Action\Select::create($rowSelect, 'disable', 'fa fa-fw fa-times'))
            ->setConfirmStr('Disable the selected users?')
            ->addOnSelect(function(\Tt\Table\Action\Select $action, array $selected) {
                foreach ($selected as $userId) {
                    $u = \Bs\Db\User::find($userId);
                    $u->active = false;
                    $u->save();
                }
            });

        $this->appendAction(Csv::create($rowSelect))
            ->addOnCsv(function(Csv $action, array $selected) {
                $action->setExcluded(['id', 'actions', 'permissions']);
                $this->getCell('username')->getOnValue()->reset();
                $this->getCell('email')->getOnValue()->reset();    // remove html from cell
                $filter = $this->getDbFilter();
                if ($selected) {
                    $filter['type'] = $this->type;
                    $filter['userId'] = $selected;
                    $rows = \Bs\Db\User::findFiltered($filter);
                } else {
                    $rows = \Bs\Db\User::findFiltered($filter->resetLimits());
                }
                return $rows;
            });

        return $this;
    }

    public function execute(): static
    {
        if (isset($GET[Masquerade::QUERY_MSQ])) {
            $this->doMsq(intval($_GET[Masquerade::QUERY_MSQ] ?? 0));
        }

        parent::execute();

        return $this;
    }

    private function doMsq(int $userId): void
    {
        /** @var \Bs\Db\User $msqUser */
        $msqUser = \Bs\Db\User::find($userId);
        if ($msqUser && Masquerade::masqueradeLogin($this->getFactory()->getAuthUser(), $msqUser)) {
            Alert::addSuccess('You are now logged in as user ' . $msqUser->username);
            if ($msqUser->getHomeUrl()) {
                $msqUser->getHomeUrl()->redirect();
            }
            Uri::create('/')->redirect();
        }

        Alert::addWarning('You cannot login as user ' . $msqUser->username . ' invalid permissions');
        Uri::create()->remove(Masquerade::QUERY_MSQ)->redirect();
    }

}