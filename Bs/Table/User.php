<?php
namespace Bs\Table;

use Bs\Db\UserMap;
use Bs\Util\Masquerade;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Alert;
use Tk\Traits\SystemTrait;
use Tk\Ui\Link;
use Tk\Uri;
use Tk\Form;
use Tk\Form\Field;
use Tk\FormRenderer;
use Tk\Table;
use Tk\Table\Cell;
use Tk\Table\Action;
use Tk\TableRenderer;

class User
{
    use SystemTrait;

    protected Table $table;

    protected ?Form $filter = null;

    protected string $type = \Bs\Db\User::TYPE_USER;


    public function __construct()
    {
        $this->table = new Table('users');
        $this->filter = new Form($this->table->getId() . '-filters');
    }

    private function doDelete($user_id)
    {
        /** @var \Bs\Db\User $user */
        $user = $this->getFactory()->getUserMap()->find($user_id);
        $user?->delete();

        Alert::addSuccess('User removed successfully.');
        Uri::create()->reset()->redirect();
    }

    private function doMsq($id)
    {
        /** @var \Bs\Db\User $msqUser */
        $msqUser = $this->getFactory()->getUserMap()->find($id);
        if ($msqUser && Masquerade::masqueradeLogin($this->getFactory()->getAuthUser(), $msqUser)) {
            Alert::addSuccess('You are now logged in as user ' . $msqUser->getUsername());
            Uri::create('/')->redirect();
        }

        Alert::addWarning('You cannot login as user ' . $msqUser->getUsername() . ' invalid permissions');
        Uri::create()->remove(Masquerade::QUERY_MSQ)->redirect();
    }

    public function doDefault(Request $request, string $type)
    {
        $this->type = $type;
        if ($request->query->has('del')) {
            $this->doDelete($request->query->get('del'));
        }
        if ($request->query->has(Masquerade::QUERY_MSQ)) {
            $this->doMsq($request->query->get(Masquerade::QUERY_MSQ));
        }
        $editUrl = sprintf('/user/%sEdit', $this->type);

        $this->getTable()->appendCell(new Cell\Checkbox('id'));
        $this->getTable()->appendCell(new Cell\Text('actions'))
            ->addOnShow(function (Cell\Text $cell) use ($editUrl) {
                $cell->addCss('text-nowrap text-center');
                $obj = $cell->getRow()->getData();

                $template = $cell->getTemplate();
                $btn = new Link('Edit');
                $btn->setText('');
                $btn->setIcon('fa fa-edit');
                $btn->addCss('btn btn-primary');
                $btn->setUrl(Uri::create($editUrl)->set('id', $obj->getId()));
                $template->appendTemplate('td', $btn->show());
                $template->appendHtml('td', '&nbsp;');

                $btn = new Link('Masquerade');
                $btn->setText('');
                $btn->setIcon('fa fa-user-secret');
                $btn->addCss('btn btn-outline-dark');
                $btn->setUrl(Uri::create()->set(Masquerade::QUERY_MSQ, $obj->getId()));
                $btn->setAttr('data-confirm', 'Are you sure you want to log-in as user \''.$obj->getName().'\'');
                $template->appendTemplate('td', $btn->show());
                $template->appendHtml('td', '&nbsp;');

                $btn = new Link('Delete');
                $btn->setText('');
                $btn->setIcon('fa fa-trash');
                $btn->addCss('btn btn-danger');
                $btn->setUrl(Uri::create()->set('del', $obj->getId()));
                $btn->setAttr('data-confirm', 'Are you sure you want to delete \''.$obj->getName().'\'');
                $template->appendTemplate('td', $btn->show());

            });

        $this->getTable()->appendCell(new Cell\Text('username'))
            ->setUrl(Uri::create($editUrl))
            ->setAttr('style', 'width: 100%;');

        $this->getTable()->appendCell(new Cell\Text('name'));

        if ($this->type == \Bs\Db\User::TYPE_STAFF) {
            $this->getTable()->appendCell(new Cell\Text('permissions'))
                ->addOnShow(function (Cell\Text $cell) {
                    /** @var \Bs\Db\User $user */
                    $user = $cell->getRow()->getData();
                    if ($user->hasPermission(\Bs\Db\User::PERM_ADMIN)) {
                        $cell->setValue(\Bs\Db\User::PERMISSION_LIST[\Bs\Db\User::PERM_ADMIN]);
                        return;
                    }
                    $list = array_filter(\Bs\Db\User::PERMISSION_LIST, function ($k) use ($user) {
                        return $user->hasPermission($k);
                    }, ARRAY_FILTER_USE_KEY);
                    $cell->setValue(implode(', ', $list));
                });
        }

        $this->getTable()->appendCell(new Cell\Text('email'))
            ->addOnShow(function (Cell\Text $cell) {
                /** @var \Bs\Db\User $user */
                $user = $cell->getRow()->getData();
                $cell->setUrl('mailto:'.$user->getEmail());
            });
        $this->getTable()->appendCell(new Cell\Text('active'));
        //$this->getTable()->appendCell(new Cell\Text('modified'));
        $this->getTable()->appendCell(new Cell\Text('created'));


        // Table filters
        $this->getFilter()->appendField(new Field\Input('search'))->setAttr('placeholder', 'Search');


        // Load filter values
        $this->getFilter()->setFieldValues($this->getTable()->getTableSession()->get($this->getFilter()->getId(), []));

        $this->getFilter()->appendField(new Form\Action\Submit('Search', function (Form $form, Action\ActionInterface $action) {
            $this->getTable()->getTableSession()->set($this->getFilter()->getId(), $form->getFieldValues());
            Uri::create()->redirect();
        }))->setGroup('');
        $this->getFilter()->appendField(new Form\Action\Submit('Clear', function (Form $form, Action\ActionInterface $action) {
            $this->getTable()->getTableSession()->set($this->getFilter()->getId(), []);
            Uri::create()->redirect();
        }))->setGroup('')->addCss('btn-outline-secondary');

        $this->getFilter()->execute($request->request->all());


        // Table Actions
        if ($this->getConfig()->isDebug()) {
            $this->getTable()->appendAction(new Action\Link('reset', Uri::create()->set(Table::RESET_TABLE, $this->getTable()->getId()), 'fa fa-retweet'))
                ->setLabel('')
                ->setAttr('data-confirm', 'Are you sure you want to reset the Table`s session?')
                ->setAttr('title', 'Reset table filters and order to default.');
        }
        //$this->getTable()->appendAction(new Action\Button('Create'))->setUrl(Uri::create('/userEdit')->set('type', $this->type));
        $this->getTable()->appendAction(new Action\Delete());
        $this->getTable()->appendAction(new Action\Csv())->addExcluded('actions');


        // Query
        $tool = $this->getTable()->getTool();
        $filter = $this->getFilter()->getFieldValues();
        $filter['type'] = $this->type;
        $list = $this->getFactory()->getUserMap()->findFiltered($filter, $tool);
        $this->getTable()->setList($list, $tool->getFoundRows());

        $this->getTable()->execute($request);
    }

    public function show(): ?Template
    {
        $renderer = new TableRenderer($this->getTable());
        //$renderer->setFooterEnabled(false);
        $this->getTable()->getRow()->addCss('text-nowrap');
        $this->getTable()->addCss('table-hover');

        if ($this->getFilter()) {
            $this->getFilter()->addCss('row gy-2 gx-3 align-items-center');
            $filterRenderer = FormRenderer::createInlineRenderer($this->getFilter());
            $renderer->getTemplate()->appendTemplate('filters', $filterRenderer->show());
            $renderer->getTemplate()->setVisible('filters');
        }

        return $renderer->show();
    }

    public function getTable(): Table
    {
        return $this->table;
    }

    public function getFilter(): ?Form
    {
        return $this->filter;
    }
}