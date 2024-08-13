<?php
namespace Bs\Table;

use Bs\Table;
use Bs\Util\Masquerade;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Alert;
use Tk\Form\Field\Input;
use Tk\Form\Field\Select;
use Tk\Uri;
use Tt\Db;
use Tt\Table\Action\Csv;
use Tt\Table\Action\Delete;
use Tt\Table\Cell;
use Tt\Table\Cell\RowSelect;

class User extends Table
{
    protected string $type = '';


    public function init(Request $request): static
    {
        $rowSelect = RowSelect::create('id', 'userId');
        $this->appendCell($rowSelect);

        $this->appendCell('actions')
            ->addCss('text-nowrap text-center')
            ->addOnValue(function(\Bs\Db\User $user, Cell $cell) {
                $edit = Uri::create('/user/'.$user->type.'Edit', ['userId' => $user->userId]);
                $msq  = Uri::create()->set(Masquerade::QUERY_MSQ, $user->userId);
                $del  = Uri::create()->set('del', $user->userId);

                return <<<HTML
                    <a class="btn btn-primary" href="$edit" title="Edit"><i class="fa fa-fw fa-edit"></i></a> &nbsp;
                    <a class="btn btn-outline-dark" href="$msq" title="Masquerade" data-confirm="Are you sure you want to log-in as user {$user->getName()}"><i class="fa fa-fw fa-user-secret"></i></a> &nbsp;
                    <a class="btn btn-danger" href="$del" title="Delete" data-confirm="Are you sure you want to delete this record"><i class="fa fa-fw fa-trash"></i></a>
                HTML;
            });

        $this->appendCell('username')
            ->addHeaderCss('max-width')
            ->setSortable(true)
            ->addOnValue(function(\Bs\Db\User $user, Cell $cell) {
                $url = Uri::create('/user/'.$user->type.'Edit', ['userId' => $user->userId]);
                return sprintf('<a href="%s">%s</a>', $url, $user->username);
            });

        $this->appendCell('nameFirst')
            ->setSortable(true);

        $this->appendCell('nameLast')
            ->setSortable(true);

        if (!$this->getType()) {
            $this->appendCell('type')
                ->setSortable(true);
        }

        if ($this->getType() != \Bs\Db\User::TYPE_MEMBER) {
            $this->appendCell('permissions')
                ->addOnValue(function(\Bs\Db\User $user, Cell $cell) {
                    if ($user->hasPermission(\Bs\Db\User::PERM_ADMIN)) {
                        $list = $user->getAvailablePermissions();
                        return $list[\Bs\Db\User::PERM_ADMIN];
                    }
                    $list = array_filter($user->getAvailablePermissions(), function ($k) use ($user) {
                        return $user->hasPermission($k);
                    }, ARRAY_FILTER_USE_KEY);
                    return implode(', <br/>', $list);
                });
        }

        $this->appendCell('email')
            ->setSortable(true)
            ->addOnValue(function(\Bs\Db\User $user, Cell $cell) {
                return sprintf('<a href="mailto:%s">%s</a>', $user->email, $user->email);
            });

        $this->appendCell('active')
            ->setSortable(true)
            ->addOnValue('\Tt\Table\Type\Boolean::onValue');

        $this->appendCell('created')
            ->setSortable(true)
            ->addOnValue('\Tt\Table\Type\DateFmt::onValue');


        // Add Filter Fields
        $this->getForm()->appendField(new Input('search'))
            ->setAttr('placeholder', 'Search: uid, name, email, username');

        if (!$this->getType()) {
            $list = array_flip(\Bs\Db\User::TYPE_LIST);
            $this->getForm()->appendField(new Select('type', $list))->prependOption('-- Type --', '');;
        }

        // init filter fields for actions to access to the filter values
        $this->initForm($request);

        // Add Table actions
        // todo: I think we should remove delete user action, create a cmd action to delete and clean users
        //       allow site users to make a user active/inactive only
        $this->appendAction(Delete::create($rowSelect))
            ->addOnDelete(function(Delete $action, array $selected) {
                foreach ($selected as $user_id) {
                    Db::delete('user', compact('user_id'));
                }
            });

        $this->appendAction(Csv::create($rowSelect))
            ->addOnCsv(function(Csv $action, array $selected) {
                $action->setExcluded(['id', 'actions', 'permissions']);
                $action->getTable()->getCell('username')->getOnValue()->reset();
                $action->getTable()->getCell('email')->getOnValue()->reset();    // remove html from cell
                $filter = $action->getTable()->getDbFilter();
                if (count($selected)) {
                    $rows = \Bs\Db\User::findFiltered($filter);
                } else {
                    $rows = \Bs\Db\User::findFiltered($filter->resetLimits());
                }
                return $rows;
            });

        return $this;
    }

    public function execute(Request $request): static
    {
        if ($request->query->has('del')) {
            $this->doDelete($request->query->get('del'));
        }

        if ($request->query->has(Masquerade::QUERY_MSQ)) {
            $this->doMsq($request->query->get(Masquerade::QUERY_MSQ));
        }

        parent::execute($request);

        return $this;
    }

//    public function findList(array $filter = [], ?Tool $tool = null): null|array|Result
//    {
//        if (!$tool) $tool = $this->getTool();
//        $filter = array_merge($this->getFilterForm()->getFieldValues(), $filter);
//        //$list = $this->getFactory()->getUserMap()->findFiltered($filter, $tool);
//        $list = \Bs\Db\User::findFiltered($filter);
//        $this->setList($list);
//        return $list;
//    }

    private function doDelete($userId): void
    {
        /** @var \Bs\Db\User $user */
        $user = \Bs\Db\User::find($userId);
        $user?->delete();

        Alert::addSuccess('User removed successfully.');
        Uri::create()->reset()->redirect();
    }

    private function doMsq($userId): void
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

//    public function show(): ?Template
//    {
//        $renderer = $this->getTableRenderer();
//        $this->getRow()->addCss('text-nowrap');
//        $this->showFilterForm();
//        return $renderer->show();
//    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): User
    {
        $this->type = $type;
        return $this;
    }

}