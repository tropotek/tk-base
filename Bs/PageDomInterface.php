<?php
namespace Bs;

use Dom\Modifier;
use Dom\Renderer\DisplayInterface;
use Dom\Renderer\RendererInterface;
use Dom\Renderer\Traits\RendererTrait;
use Dom\Template;
use Tk\Log;

class PageDomInterface extends PageInterface implements RendererInterface
{
    use RendererTrait;

    protected ?Modifier $domModifier = null;

    /**
     * Return the rendered page with all content
     * This will be called by the page handler to get the final page HTML
     */
    public function getHtml(): string
    {
        $template = $this->show();
        $this->getDomModifier()?->execute($template->getDocument());
        return $template->toString();
    }

    public function setDomModifier(?Modifier $domModifier): PageDomInterface
    {
        $this->domModifier = $domModifier;
        return $this;
    }

    public function getDomModifier(): ?Modifier
    {
        return $this->domModifier;
    }

    public function loadTemplate(string $xhtml = ''): ?\Dom\Template
    {
        return Factory::instance()->getTemplateLoader()->load($xhtml);
    }

    public function loadTemplateFile(string $path = ''): ?\Dom\Template
    {
        return Factory::instance()->getTemplateLoader()->loadFile($path);
    }

    /**
     * Execute the rendering of a template.
     */
    public function show(): ?Template
    {
        $template = $this->getTemplate();
        foreach ($this->getContentList() as $var => $list) {
            foreach ($list as $renderer) {
                if (is_string($renderer)) {
                    $this->getTemplate()->appendHtml($var, $renderer);
                } else {
                    if ($renderer instanceof DisplayInterface) {
                        $renderer = $renderer->show();
                    }
                    if ($renderer instanceof Template) {
                        $this->getTemplate()->appendTemplate($var, $renderer);
                    }
                    if ($renderer instanceof \DOMDocument) {
                        $this->getTemplate()->appendDocHtml($var, $renderer);
                    }
                }
            }
        }
        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $template = '';
        if (is_file($this->getTemplatePath())) {
            $template = $this->loadTemplateFile($this->getTemplatePath());
        }
        if (!$template) {
            Log::debug('WARNING! Using default Template, please check template path: ' . $this->getTemplatePath());
            $html = <<<HTML
<html>
<head>
  <title></title>
</head>
<body>
  <div var="content"></div>
</body>
</html>
HTML;
            $template = $this->loadTemplate($html);
        }

        return $template;
    }
}