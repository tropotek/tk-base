<?php
namespace Bs;

use Dom\Renderer\RendererInterface;
use Dom\Renderer\Traits\RendererTrait;
use Dom\Template;
use Tk\Config;

abstract class ControllerDomInterface extends ControllerInterface implements RendererInterface
{
    use RendererTrait;


    public function loadTemplate(string $html = ''): ?\Dom\Template
    {
        return Template::load($html);
    }

    public function loadTemplateFile(string $path = ''): ?\Dom\Template
    {
        return Template::loadFile($path);
    }
}