<?php

namespace Bs;

use Dom\Renderer\Traits\RendererTrait;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Form;
use Tk\Traits\SystemTrait;
use Tk\Uri;
use Tt\DbFilter;
use Tt\Table\DomRenderer;
use Tk\Form\Renderer\Dom\Renderer;

class Table extends \Tt\Table
{
    use SystemTrait;
    use RendererTrait;

    protected ?Form        $form         = null;
    protected ?DbFilter    $dbFilter     = null;
    protected ?DomRenderer $renderer     = null;
    protected ?Renderer    $formRenderer = null;


    public function __construct(string $tableId = 'tbl', string $orderBy = '', int $limit = 10, int $page = 1)
    {
        parent::__construct($tableId);
        $this->setLimit($_GET[$this->makeRequestKey(\Tt\Table::PARAM_LIMIT)] ?? $limit);
        $this->setPage($_GET[$this->makeRequestKey(\Tt\Table::PARAM_PAGE)] ?? $page);
        $this->setOrderBy($_GET[$this->makeRequestKey(\Tt\Table::PARAM_ORDERBY)] ?? $orderBy);

        $path = $this->getConfig()->makePath(
            $this->getConfig()->get('path.vendor.org').'/tk-framework/Tt/Table/templates/bs5_dom.html'
        );
        $this->renderer = new DomRenderer($this, $path);

    }

    public function init(Request $request): static
    {
        $values = [];
        if (!is_null($this->form)) {
            $this->form->appendField(new Form\Action\Submit('filter', function (Form $form, Form\Action\ActionInterface $action) {
                $values = $form->getFieldValues();
                $_SESSION[$this->makeRequestKey('filter')] = $values;
                Uri::create()->redirect();
            }))->setLabel('Search');
            $this->form->appendField(new Form\Action\Submit('clear', function (Form $form, Form\Action\ActionInterface $action) {
                unset($_SESSION[$this->makeRequestKey('filter')]);
                Uri::create()->redirect();
            }))->addCss('btn-outline-secondary');

            $this->form->execute($request->request->all());
            if (!$this->form->isSubmitted() && isset($_SESSION[$this->makeRequestKey('filter')])) {
                $this->form->setFieldValues($_SESSION[$this->makeRequestKey('filter')]);
            }
            $values = $this->form->getFieldValues();

        }

        // create DbFilter
        $this->dbFilter = DbFilter::createFromTable($values, $this);

        return $this;
    }

    public function execute(Request $request): static
    {
        parent::execute($request);

        return $this;
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();

        // Render filter form
        $template->appendTemplate('table', $this->formRenderer->show());

        // Render table
        $template->appendTemplate('table', $this->getRenderer()->show());

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div class="tk-table-panel" var="table"></div>
HTML;
        return $this->loadTemplate($html);
    }

    public function getFilterForm(): Form
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

    public function getRows(): ?array
    {
        return $this->getRenderer()->getRows();
    }

    public function setRows(array $rows, ?int $totalRows = null): static
    {
        $this->getRenderer()->setRows($rows, $totalRows);
        return $this;
    }

    public function getDbFilter(): ?DbFilter
    {
        return $this->dbFilter;
    }

    public function getRenderer(): ?DomRenderer
    {
        return $this->renderer;
    }

    public function getFormRenderer(): ?Renderer
    {
        return $this->formRenderer;
    }

}