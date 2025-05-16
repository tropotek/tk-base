<?php

namespace Bs\Mvc;

use Dom\Renderer\Traits\RendererTrait;
use Dom\Template;
use Tk\Config;
use Tk\DataMap\DataMap;
use Tk\Form;
use Tk\Log;
use Tk\Path;
use Tk\Table\Cell;
use Tk\Uri;
use Tk\Db\Filter;
use Tk\Table\Action;
use Tk\Table\DomRenderer;
use Tk\Form\Renderer\Dom\Renderer;

class Table extends \Tk\Table
{
    use RendererTrait;

    protected ?Form        $form           = null;
    protected ?Table       $table          = null;
    protected ?Filter      $dbFilter       = null;
    protected ?DomRenderer $renderer       = null;
    protected ?Renderer    $formRenderer   = null;
    protected bool         $hideReset      = false;
    protected array        $filterDefaults = [];


    public function __construct(string $tableId = '', string $orderBy = '', int $limit = 10, int $page = 1)
    {
        // create a unique table id if none supplied
        if (empty($tableId)) {
            $trace = debug_backtrace()[0] ?? ['file' => '/tbl', 'line' => 1];
            $tableId = hash('crc32', $trace['file'].$trace['line']);
        }
        parent::__construct($tableId);

        $this->setOrderBy($orderBy);
        $this->setLimit($limit);
        $this->setPage($page);

        $this->table = $this;
        $this->renderer = new DomRenderer($this);
    }

    /**
     * Override this method to add your cells, filters, actions
     */
    public function init(): static
    {
        return $this;
    }

    /**
     * The execute method should be called after all cells and filters have been added
     */
    public function execute(): static
    {
        // init cells, filters and actions
        $this->init();
        if (count($this->filterDefaults)) {
            $this->getForm()->setFieldValues($this->filterDefaults);
        }

        // add reset table session action
        if (Config::isDev()) {
            $this->addResetAction();
        }

        // init/execute filter form request
        $this->initForm();

        // init the pager properties limit, page, orderBy
        $this->initPager();

        // setup filter values
        $filterValues = [];
        if ($this->form) {
            $filterValues = $this->form->getFieldValues();
        }

        if (is_null($this->dbFilter)) {
            $this->dbFilter = Filter::createFromTable($filterValues, $this);
        }

        /* @var Cell $action */
        foreach ($this->getCells() as $cells) {
            $cells->execute();
        }

        /* @var Action $action */
        foreach ($this->getActions() as $action) {
            $action->execute();
        }

        return $this;
    }

    public function initForm(): static
    {
        if ($this->form && !$this->form->getField('filter')) {
            $this->form->appendField(new Form\Action\Submit('filter', function (Form $form, Form\Action\ActionInterface $action) {
                $values = $form->getFieldValues();
                $ses = $this->getTableSession();
                $ses->set('filter', $values);
                $ses->set($this->makeRequestKey(self::PARAM_PAGE), 1);
                Uri::create()->redirect();
            }))->setLabel('Search');

            $this->form->appendField(new Form\Action\Submit('clear', function (Form $form, Form\Action\ActionInterface $action) {
                $ses = $this->getTableSession();
                $ses->remove('filter');
                $ses->set($this->makeRequestKey(self::PARAM_PAGE), 1);
                Uri::create()->redirect();
            }))->addCss('btn-outline-secondary');

            $this->form->execute($_POST);

            $ses = $this->getTableSession();
            if (!$this->form->isSubmitted() && is_array($ses->get('filter'))) {
                $this->form->setFieldValues($ses['filter']);
            }
        }

        return $this;
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();

        $template->setAttr('table', 'id', $this->getWrapId());

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

    public function getWrapId(): string
    {
        return str_replace('_', '-', $this->makeRequestKey('wrap'));
    }

    /**
     * get the filter form, create instance if null
     */
    public function getForm(): Form
    {
        if (!$this->form) {
            $this->form = new Form($this->getId().'f');
            $this->form->setCsrfTtl(0);
            $this->form->addCss('tk-table-filter');
            // Inline Dom Form Renderer
            $tplFile = Path::create('/vendor/ttek/tk-form/templates/bs5_dom_inline.html');
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

    public function hideReset(bool $hideReset = true): static
    {
        $this->hideReset = $hideReset;
        return $this;
    }

    public function validateCells(DataMap $map): bool
    {
        /** @var Cell $cell */
        foreach ($this->getCells() as $cell) {
            if (!$cell->isSortable()) continue;
            if (!in_array($cell->getOrderBy(), $map->getColumnNames())) {
                Log::error("invalid order by property for cell: {$cell->getName()} ({$cell->getOrderBy()})");
                return false;
            }
        }
        return true;
    }

    public function addResetAction(): ?Action
    {
        if ($this->hideReset) return null;

        return $this->prependAction('__reset')
            ->addOnExecute(function (Action $action) {
                $val = $action->getTable()->makeRequestKey($action->getName());
                $active = ($_POST[$action->getName()] ?? '') == $val;
                if (!$active) return;

                $this->resetTableSession();

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

    /**
     * Change the main table template to
     * Call this in place of $table->show(); for htmx components
     */
    public function htmxShow(): ?Template
    {
        $baseUrl = Uri::create();

        // setup table for hx
        $ttpl = $this->getRenderer()->getTemplate();

        // setup hx on all links and elements in the template
        $wrapId = $this->getWrapId();

        if ($this->getFormRenderer()) {
            $ftpl = $this->getFormRenderer()->getTemplate();
            $ftpl->setAttr('form', 'hx-post', $baseUrl);
            $ftpl->removeAttr('form', 'action');
            $ftpl->setAttr('form', 'hx-swap', 'outerHTML');
            $ftpl->setAttr('form', 'hx-target', "#$wrapId");
            $ftpl->setAttr('form', 'hx-select', "#$wrapId");
        }

        if ($ttpl instanceof Template) {
            $ttpl->setAttr('form', 'hx-post', $baseUrl);
            $ttpl->removeAttr('form', 'action');
            $ttpl->removeAttr('form', 'method');
            $ttpl->setAttr('form', 'hx-swap', 'outerHTML');
            $ttpl->setAttr('form', 'hx-target', "#$wrapId");
            $ttpl->setAttr('form', 'hx-select', "#$wrapId");
            $ttpl->setAttr('limit-select', 'hx-post', $baseUrl);

            $ttpl = $this->show();

            // Hack to get all pager buttons to submit via hx
            $xpath = new \DOMXPath($ttpl->getDocument());

            // convert all header sort links
            $links = $xpath->query("//th/a[contains(@href, '_orderBy=')]");
            if ($links instanceof \DOMNodeList) {
                for ($i = $links->length - 1; $i > -1; $i--) {
                    $node = $links->item($i)->firstChild->parentElement;
                    if (!($node instanceof \DOMElement)) continue;
                    $url = $node->getAttribute('href');
                    $node->setAttribute('hx-get', $url);
                }
            }

            // convert all pager links
            $links = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' page-link ')]");
            if ($links instanceof \DOMNodeList) {
                for ($i = $links->length - 1; $i > -1; $i--) {
                    $node = $links->item($i)->firstChild->parentElement;
                    if (!($node instanceof \DOMElement)) continue;
                    $url = $node->getAttribute('href');
                    $node->setAttribute('hx-get', $url);
                }
            }

            // convert all limit links
            $links = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' limit-link ')]");
            if ($links instanceof \DOMNodeList) {
                for ($i = $links->length - 1; $i > -1; $i--) {
                    $node = $links->item($i)->firstChild->parentElement;
                    if (!($node instanceof \DOMElement)) continue;
                    $url = $node->getAttribute('href');
                    $node->setAttribute('hx-get', $url);
                }
            }
        }

        return $ttpl;
    }

    public function getFilterDefaults(): array
    {
        return $this->filterDefaults;
    }

    public function setFilterDefaults(array $filterDefaults): self
    {
        $this->filterDefaults = $filterDefaults;
        return $this;
    }

}