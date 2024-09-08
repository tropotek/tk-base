<?php

namespace Bs;

use Bs\Db\User;
use Dom\Renderer\Traits\RendererTrait;
use Dom\Template;
use Tk\Form;
use Tk\Traits\SystemTrait;
use Tk\Uri;
use Tk\Db\Filter;
use Tt\Table\Action;
use Tt\Table\DomRenderer;
use Tk\Form\Renderer\Dom\Renderer;

class Table extends \Tt\Table
{
    use SystemTrait;
    use RendererTrait;

    protected ?Form        $form         = null;
    protected ?Filter      $dbFilter     = null;
    protected ?DomRenderer $renderer     = null;
    protected ?Renderer    $formRenderer = null;
    protected string       $sid          = 'filter';


    public function __construct(string $tableId = 'tbl', string $orderBy = '', int $limit = 10, int $page = 1)
    {
        parent::__construct($tableId);
        $this->setLimit($_GET[$this->makeRequestKey(\Tt\Table::PARAM_LIMIT)] ?? $limit);
        $this->setPage($_GET[$this->makeRequestKey(\Tt\Table::PARAM_PAGE)] ?? $page);
        $this->setOrderBy($_GET[$this->makeRequestKey(\Tt\Table::PARAM_ORDERBY)] ?? $orderBy);
        $this->sid = $this->makeRequestKey('filter');

        $path = $this->getConfig()->makePath(
            $this->getConfig()->get('path.vendor.org').'/tk-framework/Tt/Table/templates/bs5_dom.html'
        );
        $this->renderer = new DomRenderer($this, $path);

    }

    /**
     * Override this method to add your cells, filters, actions
     */
    public function init(): static
    {
        return $this;
    }

    public function execute(): static
    {
        // add reset table session action
        // todo: not working for some reason????
        if ($this->getConfig()->isDebug()) {
            $this->addResetAction();
        }

        // init cells, filters and actions
        $this->init();

        // init filter from request if not already done
        $this->initForm();

        // execute actions and get orderby from request
        parent::execute();

        return $this;
    }

    public function initForm(): static
    {
        $values = [];
        if (!is_null($this->form) && !$this->form->getField('filter')) {
            $this->form->appendField(new Form\Action\Submit('filter', function (Form $form, Form\Action\ActionInterface $action) {
                $values = $form->getFieldValues();
                $_SESSION[$this->sid] = $values;
                Uri::create()->redirect();
            }))->setLabel('Search');
            $this->form->appendField(new Form\Action\Submit('clear', function (Form $form, Form\Action\ActionInterface $action) {
                unset($_SESSION[$this->sid]);
                Uri::create()->redirect();
            }))->addCss('btn-outline-secondary');

            $this->form->execute($_POST);

            if (!$this->form->isSubmitted() && isset($_SESSION[$this->sid])) {
                $this->form->setFieldValues($_SESSION[$this->sid]);
            }
            $values = $this->form->getFieldValues();
        }

        if (is_null($this->dbFilter)) {
            $this->dbFilter = Filter::createFromTable($values, $this);
        }

        return $this;
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();

        // Render filter form
        if ($this->formRenderer) {
            $template->appendTemplate('table', $this->formRenderer->show());
        }

        // Render table
        $template->appendTemplate('table', $this->getRenderer()->show());

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div class="bs-table-wrap" var="table"></div>
HTML;
        return $this->loadTemplate($html);
    }

    /**
     * get the filter form, create instance if null
     */
    public function getForm(): Form
    {
        if (!$this->form) {
            $this->form = new Form($this->getId().'f');
            $this->form->addCss('tk-table-filter');
            // Dom Form Renderer
            $tplFile = $this->makePath($this->getConfig()->get('path.template.form.dom.inline'));
            $this->formRenderer = new Renderer($this->form, $tplFile);
        }
        return $this->form;
    }

    public function getFormRenderer(): ?Renderer
    {
        return $this->formRenderer;
    }

    public function getRows(): ?array
    {
        return $this->getRenderer()->getRows();
    }

    public function setRows(array $rows, ?int $totalRows = null): static
    {
        $this->getRenderer()->setRows($rows, $totalRows);
        return $this;
    }

    public function getDbFilter(): ?Filter
    {
        return $this->dbFilter;
    }

    public function getRenderer(): ?DomRenderer
    {
        return $this->renderer;
    }

    public function getAuthUser(): ?User
    {
        return $this->getFactory()->getAuthUser();
    }

    public function addResetAction(): Action
    {
        return $this->appendAction('reset')
            ->addOnExecute(function (Action $action) {
                $val = $action->getTable()->makeRequestKey($action->getName());
                $active = ($_POST[$action->getName()] ?? '') == $val;
                $action->setActive($active);
                if (!$action->isActive()) return;

                unset($_SESSION[$this->sid]);
                Uri::create()
                    ->remove($action->getTable()->makeRequestKey(\Tt\Table::PARAM_PAGE))
                    ->remove($this->makeRequestKey(\Tt\Table::PARAM_LIMIT))
                    ->remove($this->makeRequestKey(\Tt\Table::PARAM_ORDERBY))
                    ->redirect();
            })
            ->addOnShow(function (Action $action) {
                $val = $action->getTable()->makeRequestKey($action->getName());
                return <<<HTML
                        <button type="submit" name="{$action->getName()}" value="$val"
                            class="tk-action-reset-tbl btn btn-sm btn-light"
                            title="Reset table session"
                            data-confirm="Are you sure you want to reset the Table`s session?">
                            <i class="fa fa-fw fa-retweet"></i>
                        </button>
                    HTML;
            });
    }

}