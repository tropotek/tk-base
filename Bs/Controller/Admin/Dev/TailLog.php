<?php
namespace Bs\Controller\Admin\Dev;

use Bs\Db\UserInterface;
use Bs\PageController;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Uri;

class TailLog extends PageController
{

    protected string $logPath = '';

    public function __construct()
    {
        parent::__construct();
        $this->getPage()->setTitle('Tail Log');
        $this->setAccess(UserInterface::PERM_ADMIN);

        $this->logPath = ini_get('error_log');
    }

    public function doDefault(Request $request)
    {
        if ($request->query->get('seek')) {
            $this->doSeek($request);
        }
        if ($request->query->get('refresh')) {
            $this->doRefresh($request);
        } else {
            $this->doSeek($request);
        }

        return $this->getPage();
    }

    public function doRefresh(Request $request)
    {
        if (!is_readable($this->logPath)) {
            echo sprintf('Cannot read log file: ' . $this->logPath . "\n");
            exit;
        }

        $session = $this->getSession();
        $handle = fopen($this->logPath, 'r');
        if ($session->get('tail-offset')) {
            $pos = $session->get('tail-offset');
            $data = stream_get_contents($handle, -1, $pos);
            echo htmlentities($data);
            $pos = ftell($handle);
            $session->set('tail-offset', $pos);
        } else {
            $this->doSeek($request, -1000);
        }
        exit();
    }

    public function doSeek(Request $request, $seekAdjust = 0)
    {
        $session = $this->getSession();
        $handle = fopen($this->logPath, 'r');
        fseek($handle, 0, \SEEK_END);
        $pos = ftell($handle);
        if ($seekAdjust > 0) {
            $pos += $seekAdjust;
        }
        if ($pos < 0) $pos = 0;
        $session->set('tail-offset', $pos);
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setAttr('back', 'href', $this->getBackUrl());

        $template->setAttr('tail', 'data-src', Uri::create()->set(\Tk\Log::NO_LOG)->set('refresh'));
        $template->appendJsUrl(Uri::create($this->getConfig()->get('path.vendor.org') . '/tk-base/Bs/Controller/Admin/Dev/jquery.tkTail.js'));
        $js = <<<JS
jQuery(function($) {
  $('#tail').tail({
    height: '600px'
  });
});
JS;
        $template->appendJs($js);

        $css = <<<CSS
.tk-tail {
  position: relative;
  background-color: #FFF;
}
.tail-out {
  position: relative;
  font-size: 0.9em;
  line-height: 1.2;
  padding-bottom: 15px;
}
CSS;
        $template->appendCss($css);

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
  <div class="card mb-3">
    <div class="card-header"><i class="fa fa-cogs"></i> Actions</div>
    <div class="card-body" var="actions">
      <a href="/" title="Back" class="btn btn-outline-secondary" var="back"><i class="fa fa-arrow-left"></i> Back</a>
    </div>
  </div>
  <div class="card mb-3">
    <div class="card-header" var="title"><i class="fa fa-road"></i> </div>
    <div class="card-body" var="content">
      <div class="tk-tail" id="tail" data-src="" var="tail"></div>
    </div>
  </div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}