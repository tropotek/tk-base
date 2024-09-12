<?php
namespace Bs;

use Dom\Renderer\RendererInterface;
use Dom\Renderer\Traits\RendererTrait;
use Tk\Config;

abstract class ControllerDomInterface extends ControllerInterface implements RendererInterface
{
    use RendererTrait;

    public function loadTemplate(string $xhtml = ''): ?\Dom\Template
    {
        return Factory::instance()->getTemplateLoader()->load($xhtml);
    }

    public function loadTemplateFile(string $path = ''): ?\Dom\Template
    {
        return Factory::instance()->getTemplateLoader()->loadFile($path);
    }
}