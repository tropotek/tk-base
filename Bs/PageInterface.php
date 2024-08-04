<?php
namespace Bs;

use Tk\Traits\SystemTrait;

abstract class PageInterface
{
    use SystemTrait;

    private string $title        = '';
    private string $templatePath = '';
    private array  $contentList  = [];
    private bool   $enabled      = true;


    public function __construct(string $templatePath = '')
    {
        $this->templatePath = $templatePath;
    }

    /**
     * Return the rendered page with all content
     * This will be called by the page handler to get the final page HTML
     */
    abstract public function getHtml(): string;


    public function addContent(mixed $renderer, string $name = ''): self
    {
        $name = $name ?: 'content';
        $this->contentList[$name][] = $renderer;
        return $this;
    }

    public function getContent($name): array
    {
        return $this->contentList[$name] ?? [];
    }

    public function getContentList(): array
    {
        return $this->contentList;
    }

    public function getTemplatePath(): string
    {
        return $this->templatePath;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): PageInterface
    {
        $this->title = $title;
        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): PageInterface
    {
        $this->enabled = $enabled;
        return $this;
    }

}