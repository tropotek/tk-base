<?php
namespace Bs\Controller\File;

use Bs\Db\UserInterface;
use Bs\PageController;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;

class Manager extends PageController
{
    protected \Bs\Table\File $table;

    protected string $fkey = '';

    public function __construct()
    {
        parent::__construct($this->getFactory()->getPublicPage());
        $this->getPage()->setTitle('File Manager');
        $this->setAccess(UserInterface::PERM_ADMIN);
    }

    public function doDefault(Request $request)
    {
        // Get the form template
        $this->table = new \Bs\Table\File($this->fkey);
        $this->table->doDefault($request);

        return $this->getPage();
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setText('title', $this->getPage()->getTitle());

        $template->appendTemplate('content', $this->table->show());

        return $template;
    }

    public function __makeTemplate()
    {
        $html = <<<HTML
<div>
  <h2 var="title"></h2>
  <div var="content"></div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}