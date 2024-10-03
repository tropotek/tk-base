<?php

namespace Bs\Mvc;

use Dom\Renderer\Traits\RendererTrait;
use Dom\Template;
use Tk\Config;
use Tk\Form;
use Tk\Uri;
use Tk\Db\Filter;
use Tk\Table\Action;
use Tk\Table\DomRenderer;
use Tk\Form\Renderer\Dom\Renderer;

class Table extends \Tk\Table
{
    use RendererTrait;

    protected ?Form        $form         = null;
    protected ?Filter      $dbFilter     = null;
    protected ?DomRenderer $renderer     = null;
    protected ?Renderer    $formRenderer = null;
    protected string       $sid          = 'filter';


    public function __construct(string $tableId = 'tbl', string $orderBy = '', int $limit = 10, int $page = 1)
    {
        parent::__construct($tableId);
        $this->setOrderBy($orderBy);
        $this->setLimit($limit);
        $this->setPage($page);
        $this->sid = $this->makeRequestKey('filter');

        $this->renderer = new DomRenderer($this);

        // add reset table session action
        if (Config::isDev()) {
            $this->addResetAction();
        }
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
        $this->setLimit($_GET[$this->makeRequestKey(\Tk\Table::PARAM_LIMIT)] ?? $this->getLimit());
        $this->setPage($_GET[$this->makeRequestKey(\Tk\Table::PARAM_PAGE)] ?? $this->getPage());
        $this->setOrderBy($_GET[$this->makeRequestKey(\Tk\Table::PARAM_ORDERBY)] ?? $this->getOrderBy());

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
        return Template::load($html);
    }

    /**
     * get the filter form, create instance if null
     */
    public function getForm(): Form
    {
        if (!$this->form) {
            $this->form = new Form($this->getId().'f');
            $this->form->addCss('tk-table-filter');
            // Inline Dom Form Renderer
            $tplFile = Config::makePath('/vendor/ttek/tk-form/templates/bs5_dom_inline.html');
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

    public function addResetAction(): Action
    {
        return $this->appendAction('__reset')
            ->addOnExecute(function (Action $action) {
                $val = $action->getTable()->makeRequestKey($action->getName());
                $active = ($_POST[$action->getName()] ?? '') == $val;
                if (!$active) return;
                unset($_SESSION[$this->sid]);
                Uri::create()
                    ->remove($action->getTable()->makeRequestKey(\Tk\Table::PARAM_PAGE))
                    ->remove($this->makeRequestKey(\Tk\Table::PARAM_LIMIT))
                    ->remove($this->makeRequestKey(\Tk\Table::PARAM_ORDERBY))
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