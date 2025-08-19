<?php
namespace Bs\Controller\Util;

use Bs\Auth;
use Bs\Mvc\ControllerAdmin;
use Bs\Mvc\Form;
use Bs\Mvc\Table;
use Dom\Template;
use Tk\Alert;
use Tk\Db;
use Tk\FileUtil;
use Tk\Form\Action\Submit;
use Tk\Form\Field\Checkbox;
use Tk\Form\Field\Input;
use Tk\Str;
use Tk\Table\Cell;

class DbSize extends ControllerAdmin
{

    protected Table $table;
    protected float $total = 0;

    public function doDefault(): void
    {
        $this->getPage()->setTitle('Database HDD Usage', 'fas fa-database');
        $this->setUserAccess(Auth::PERM_ADMIN);

        $sql = "
            SELECT
                table_name,
                table_rows,
                round((data_length + index_length) / 1024 / 1024, 2) as size_mb,
                data_length + index_length as size_bytes,
                table_name AS _search
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
            AND data_length IS NOT NULL
            ORDER BY table_name";
        $rows = Db::query($sql);

        $sizes = array_column($rows, 'size_bytes', 'table_name');
        $this->total = array_sum(array_values($sizes));
        $rows[] = (object)['table_name' => 'Total', 'table_rows' => count($rows), 'size_bytes' => $this->total];


        $this->table = new Table('db-size');
        $this->table->hideReset();
        $this->table->addCss('tk-table-sm');

        $this->table->appendCell('table_name')
            ->setHeader('Table Name')
            ->addCss('max-width');

        $this->table->appendCell('table_rows')
            ->setHeader('Row Count')
            ->addHeaderCss('text-right')
            ->addCss('text-nowrap text-right');

        $this->table->appendCell('size_bytes')
            ->setHeader('Size')
            ->addHeaderCss('text-right')
            ->addCss('text-nowrap text-right')
            ->addOnValue(function(\stdClass $obj, Cell $cell) {
                if ($obj->table_name == 'Total') {
                    $cell->getTable()->getRowAttrs()->setAttr('class', 'text-strong bg-secondary-subtle');
                }
                return FileUtil::bytes2String($obj->size_bytes);
            });

        // execute table
        $this->table->execute();

        // Set the table rows
        $this->table->setRows($rows, count($rows));
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->appendText('title', $this->getPage()->getTitle());
        $template->addCss('icon', $this->getPage()->getIcon());

        $template->setText('total', FileUtil::bytes2String($this->total));
        $template->appendTemplate('table', $this->table->show());

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div class="db-search">
  <div class="card">
    <div class="card-header"><i var="icon"></i> <span>DB Table Search</span></div>
    <div class="card-body php-info">
      <p>Total DB Size: <strong var="total">0 MB</strong></p>
      <div var="table"></div>
      </div>
    </div>
  </div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}

