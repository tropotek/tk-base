<?php
namespace Bs\Table;

use Bs\Util\Masquerade;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Alert;
use Tk\Db\Mapper\Result;
use Tk\Db\Tool;
use Tk\Ui\Link;
use Tk\Uri;
use Tk\Form\Field;
use Tk\Table\Cell;
use Tk\Table\Action;

class User extends ManagerInterface
{
    protected string $type = '';


    protected function initCells(): void
    {
        // TODO: How will we manage this with various ID's LOOK INTO IT!!!!!!!!
        //       Also check into Actions `delete`, `csv`, etc....
        $this->appendCell(new Cell\Checkbox('userId'));

        $this->appendCell(new Cell\Text('actions'))
            ->addOnShow(function (Cell\Text $cell) {
                $cell->addCss('text-nowrap text-center');
                $obj = $cell->getRow()->getData();

                $template = $cell->getTemplate();
                $btn = new Link('Edit');
                $btn->setText('');
                $btn->setIcon('fa fa-edit');
                $btn->addCss('btn btn-xs btn-primary');
                $btn->setUrl(Uri::create('/user/'.$obj->getType().'Edit')->set('userId', $obj->getId()));
                $template->appendTemplate('td', $btn->show());
                $template->appendHtml('td', '&nbsp;');

                $btn = new Link('Masquerade');
                $btn->setText('');
                $btn->setIcon('fa fa-user-secret');
                $btn->addCss('btn btn-xs btn-outline-dark');
                $btn->setUrl(Uri::create()->set(Masquerade::QUERY_MSQ, $obj->getId()));
                $btn->setAttr('data-confirm', 'Are you sure you want to log-in as user \''.$obj->getName().'\'');
                $template->appendTemplate('td', $btn->show());
                $template->appendHtml('td', '&nbsp;');

                $btn = new Link('Delete');
                $btn->setText('');
                $btn->setIcon('fa fa-trash');
                $btn->addCss('btn btn-xs btn-danger');
                $btn->setUrl(Uri::create()->set('del', $obj->getId()));
                $btn->setAttr('data-confirm', 'Are you sure you want to delete \''.$obj->getName().'\'');
                $template->appendTemplate('td', $btn->show());

            });

        $this->appendCell(new Cell\Text('username'))
            ->addOnShow(function (Cell\Text $cell, mixed $value) {
                    /** @var \Bs\Db\User $obj */
                    $obj = $cell->getRow()->getData();
                    $cell->setUrl(Uri::create('/user/'.$obj->getType().'Edit')->set('userId', $obj->getId()));
                })
            ->setAttr('style', 'width: 100%;');

        $this->appendCell(new Cell\Text('name'));

        if (!$this->getType()) {
            $this->appendCell(new Cell\Text('type'));
        }

        if ($this->getType() != \Bs\Db\User::TYPE_MEMBER) {
            $this->appendCell(new Cell\Text('permissions'))
                ->addOnShow(function (Cell\Text $cell, mixed $value) {
                    /** @var \Bs\Db\User $obj */
                    $obj = $cell->getRow()->getData();
                    if ($obj->hasPermission(\Bs\Db\User::PERM_ADMIN)) {
                        $list = $obj->getAvailablePermissions();
                        return $list[\Bs\Db\User::PERM_ADMIN];
                    }
                    $list = array_filter($obj->getAvailablePermissions(), function ($k) use ($obj) {
                        return $obj->hasPermission($k);
                    }, ARRAY_FILTER_USE_KEY);
                    return implode(', <br/>', $list);
                });
        }

        $this->appendCell(new Cell\Text('email'))
            ->addOnShow(function (Cell\Text $cell) {
                /** @var \Bs\Db\User $obj */
                $obj = $cell->getRow()->getData();
                $cell->setUrl('mailto:'.$obj->getEmail());
            });
        $this->appendCell(new Cell\Text('active'));
        //$this->appendCell(new Cell\Text('modified'));
        $this->appendCell(new Cell\Text('created'));


        // Table filters
        $this->getFilterForm()->appendField(new Field\Input('search'))->setAttr('placeholder', 'Search');
        if (!$this->getType()) {
            // TODO: Will need to get this list from a `User` or `Permission` static class
            $list = ['Staff' => \Bs\Db\User::TYPE_STAFF, 'Member' => \Bs\Db\User::TYPE_MEMBER];
            $this->getFilterForm()->appendField(new Field\Select('type', $list))->prependOption('-- Type --', '');
        }

        // Table Actions
        $this->appendAction(new Action\Delete('delete', 'userId'));
        $this->appendAction(new Action\Csv('csv', 'userId'))->addExcluded('actions');

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

    public function findList(array $filter = [], ?Tool $tool = null): null|array|Result
    {
        if (!$tool) $tool = $this->getTool();
        $filter = array_merge($this->getFilterForm()->getFieldValues(), $filter);
        $list = $this->getFactory()->getUserMap()->findFiltered($filter, $tool);
        $this->setList($list);
        return $list;
    }

    private function doDelete($userId): void
    {
        /** @var \Bs\Db\User $user */
        $user = $this->getFactory()->getUserMap()->find($userId);
        $user?->delete();

        Alert::addSuccess('User removed successfully.');
        Uri::create()->reset()->redirect();
    }

    private function doMsq($userId): void
    {
        /** @var \Bs\Db\User $msqUser */
        $msqUser = $this->getFactory()->getUserMap()->find($userId);
        if ($msqUser && Masquerade::masqueradeLogin($this->getFactory()->getAuthUser(), $msqUser)) {
            Alert::addSuccess('You are now logged in as user ' . $msqUser->getUsername());
            Uri::create('/')->redirect();
        }

        Alert::addWarning('You cannot login as user ' . $msqUser->getUsername() . ' invalid permissions');
        Uri::create()->remove(Masquerade::QUERY_MSQ)->redirect();
    }

    public function show(): ?Template
    {
        $renderer = $this->getTableRenderer();
        $this->getRow()->addCss('text-nowrap');
        $this->showFilterForm();
        return $renderer->show();
    }

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