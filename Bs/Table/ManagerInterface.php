<?php

namespace Bs\Table;

use Dom\Renderer\DisplayInterface;
use Dom\Template;
use Tk\Db\Mapper\Result;
use Tk\Db\Tool;
use Tk\Form;
use Tk\Table;
use Tk\Table\Action;
use Tk\TableRenderer;
use Tk\Uri;

/**
 * @deprecated Use \Bs\Table
 */
abstract class ManagerInterface extends Table implements DisplayInterface
{

    private ?TableRenderer $tableRenderer = null;

    private ?Form $filterForm = null;


    public function __construct(string $tableId = '')
    {
        parent::__construct($tableId);
        $this->addCss('table-hover');
    }

    public function init(): static
    {
        $this->initCells();

        if ($this->getConfig()->isDebug()) {
            $this->prependAction(new Action\Link('reset', Uri::create()->set(Table::RESET_TABLE, $this->getId()), 'fa fa-retweet'))
                ->setLabel('')
                ->setAttr('data-confirm', 'Are you sure you want to reset the Table`s session?')
                ->setAttr('title', 'Reset table filters and order to default.');
        }

        if (count($this->getFilterForm()->getFields())) {
            // Load filter values
            $this->getFilterForm()->setFieldValues($this->getTableSession()->get($this->getFilterForm()->getId(), []));

            $this->getFilterForm()->appendField(new Form\Action\Submit('Search', function (Form $form, Form\Action\ActionInterface $action) {
                $this->getTableSession()->set($this->getFilterForm()->getId(), $form->getFieldValues());
                Uri::create()->redirect();
            }))->setGroup('');

            $this->getFilterForm()->appendField(new Form\Action\Submit('Clear', function (Form $form, Form\Action\ActionInterface $action) {
                $this->getTableSession()->set($this->getFilterForm()->getId(), []);
                Uri::create()->redirect();
            }))->setGroup('')->addCss('btn-outline-secondary');

            $this->getFilterForm()->execute($_POST);
        }

        return $this;
    }

    /**
     * Add table cells, actions, and filters here
     */
    abstract protected function initCells(): void;

//    public function execute(Request $request): static
//    {
//        return parent::execute($request);
//    }

    public function findList(array $filter = [], ?Tool $tool = null): null|array|Result
    {
        $list = [];
        // TODO: replace this with your own call code
        //if (!$tool) $tool = $this->getTool();
        //$filter = array_merge($this->getFilterValues(), $filter);
        //$list = UserMap::create()->findFiltered($filter, $tool);
        return $list;
    }

    public function show(): ?Template
    {
        $renderer = $this->getTableRenderer();
        //$renderer->setFooterEnabled(false);
        $this->showFilterForm();
        return $renderer->show();
    }

    protected function showFilterForm(): void
    {
        if (count($this->getFilterForm()->getFields())) {
            $this->getFilterForm()->addCss('row gy-2 gx-3 align-items-center');
            $filterRenderer = Form\Renderer\Dom\Renderer::createInlineRenderer($this->getFilterForm());
            //$filterRenderer = Form\Renderer\Std\Renderer::createInlineRenderer($this->getFilterForm());
            $this->getTableRenderer()->getTemplate()->appendHtml('filters', $filterRenderer->show());
            $this->getTableRenderer()->getTemplate()->setVisible('filters');
        }
    }


    protected function initTableRenderer(): static
    {
        $this->tableRenderer = new TableRenderer($this);
        return $this;
    }

    protected function initFilterForm(): static
    {
        $this->filterForm = new Form($this->getId() . '-filters');
        return $this;
    }

    public function getTableRenderer(): TableRenderer
    {
        if (!$this->tableRenderer) $this->initTableRenderer();
        return $this->tableRenderer;
    }

    public function getFilterForm(): Form
    {
        if (!$this->filterForm) $this->initFilterForm();
        return $this->filterForm;
    }

    public function getBackUrl(): \Tk\Uri
    {
        return $this->getFactory()->getBackUrl();
    }

}