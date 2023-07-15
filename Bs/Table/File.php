<?php
namespace Bs\Table;

use Bs\Db\FileMap;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Alert;
use Tk\Db\Mapper\Result;
use Tk\Db\Tool;
use Tk\Table\Cell;
use Tk\Table\Action;
use Tk\Ui\Link;
use Tk\Uri;

class File extends ManagerInterface
{

    protected string $fkey = '';


    protected function initCells(): void
    {
        $this->appendCell(new Cell\Checkbox('id'));
        $this->appendCell(new Cell\Text('actions'))
            ->addOnShow(function (Cell\Text $cell) {
                $cell->addCss('text-nowrap text-center');
                /** @var \Bs\Db\File $obj */
                $obj = $cell->getRow()->getData();

                $template = $cell->getTemplate();
                $btn = new Link('View');
                $btn->setAttr('target', '_blank');
                $btn->setText('');
                $btn->setIcon('fa fa-eye');
                $btn->addCss('btn btn-success');
                $btn->setUrl($obj->getUrl());
                $template->appendTemplate('td', $btn->show());
                $template->appendHtml('td', '&nbsp;');

                $btn = new Link('Delete');
                $btn->setText('');
                $btn->setIcon('fa fa-trash');
                $btn->addCss('btn btn-danger');
                $btn->setUrl(Uri::create()->set('del', $obj->getId()));
                $btn->setAttr('data-confirm', 'Are you sure you want to delete \''.$obj->getPath().'\'');
                $template->appendTemplate('td', $btn->show());

            });

        $this->appendCell(new Cell\Text('path'))->setAttr('style', 'width: 100%;')
            ->setUrl(Uri::create('/fileEdit'))
            ->addOnShow(function (Cell\Text $cell) {
                $obj = $cell->getRow()->getData();
                $cell->setUrlProperty('');
                $cell->setUrl($obj->getUrl());
                $cell->getLink()->setAttr('target','_blank');
            });

        $this->appendCell(new Cell\Text('userId'));
        $this->appendCell(new Cell\Text('fkey'))->setLabel('Key');
        $this->appendCell(new Cell\Text('fid'))->setLabel('Key ID');
        $this->appendCell(new Cell\Text('bytes'));
        $this->appendCell(new Cell\Boolean('selected'));
        $this->appendCell(new Cell\Text('created'));

        // Table filters
        //$this->getFilterForm()->appendField(new Field\Input('search'))->setAttr('placeholder', 'Search');

        // Table Actions
        //$this->table->appendAction(new Action\Button('Create'))->setUrl(Uri::create('/userEdit')->set('type', $this->type));
        $this->appendAction(new Action\Delete());
        $this->appendAction(new Action\Csv())->addExcluded('actions');

    }

    public function execute(Request $request): static
    {
        if ($request->query->has('del')) {
            $this->doDelete($request->query->get('del'));
        }

        parent::execute($request);
        return $this;
    }

    public function findList(array $filter = [], ?Tool $tool = null): null|array|Result
    {
        if (!$tool) $tool = $this->getTool();
        $filter = array_merge($this->getFilterForm()->getFieldValues(), $filter);
        $list = FileMap::create()->findFiltered($filter, $tool);
        $this->setList($list);
        return $list;
    }

    private function doDelete($id): void
    {
        $file = FileMap::create()->find($id);
        $file?->delete();
        Alert::addSuccess('File removed successfully.');
        Uri::create()->reset()->redirect();
    }

    public function show(): ?Template
    {
        $renderer = $this->getTableRenderer();
        $renderer->setFooterEnabled(false);
        $this->getRow()->addCss('text-nowrap');
        $this->showFilterForm();
        return $renderer->show();
    }

    public function getFkey(): string
    {
        return $this->fkey;
    }

    public function setFkey(string $fkey): File
    {
        $this->fkey = $fkey;
        return $this;
    }

}