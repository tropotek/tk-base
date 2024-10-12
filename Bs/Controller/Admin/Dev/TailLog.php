<?php
namespace Bs\Controller\Admin\Dev;

use Bs\Auth;
use Bs\Mvc\ControllerAdmin;
use Dom\Template;
use JetBrains\PhpStorm\NoReturn;
use Tk\Uri;

class TailLog extends ControllerAdmin
{
    protected string $logPath = '';

    public function doDefault(): void
    {
        $this->getPage()->setTitle('Tail Log');
        $this->setAccess(Auth::PERM_ADMIN);

        $this->logPath = ini_get('error_log');


        if (isset($_GET['seek'])) {
            $this->doSeek();
        }
        if (isset($_GET['refresh'])) {
            $this->doRefresh();
        } else {
            $this->doSeek();
        }

    }

    public function doRefresh(): void
    {
        if (!is_readable($this->logPath)) {
            echo sprintf("Cannot read log file: %s\n", $this->logPath);
            exit;
        }

        $handle = fopen($this->logPath, 'r');
        if (isset($_SESSION['tail-offset'])) {
            $pos = $_SESSION['tail-offset'];
            $data = stream_get_contents($handle, -1, $pos);
            echo htmlentities($data);
            $pos = ftell($handle);
            $_SESSION['tail-offset'] = $pos;
        } else {
            $this->doSeek(-1000);
        }
        exit();
    }

    public function doSeek(int $seekAdjust = 0): void
    {
        $handle = fopen($this->logPath, 'r');
        fseek($handle, 0, \SEEK_END);
        $pos = ftell($handle);
        if ($seekAdjust > 0) {
            $pos += $seekAdjust;
        }
        if ($pos < 0) $pos = 0;
        $_SESSION['tail-offset'] = $pos;
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
  <div class="page-actions card mb-3">
    <div class="card-header"><i class="fa fa-cogs"></i> Actions</div>
    <div class="card-body" var="actions">
      <a href="/" title="Back" class="btn btn-outline-secondary" var="back"><i class="fa fa-arrow-left"></i> Back</a>
    </div>
  </div>
  <div class="card mb-3">
    <div class="card-header" var="title"><i class="fa fa-road"></i> Tail Log</div>
    <div class="card-body" var="content">
      <div class="tk-tail" id="tail" data-src="" var="tail"></div>
    </div>
  </div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}