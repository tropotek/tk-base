<?php
namespace Bs;

use Bs\Traits\SystemTrait;

abstract class PageInterface
{
    use SystemTrait;

    private string $title        = '';
    private array  $contentList  = [];
    private array  $options      = [];
    private bool   $enabled      = true;
    private string $templatePath;


    public function __construct(string $templatePath = '', array $options = [])
    {
        $this->templatePath = $templatePath;
        $this->options = $options;
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

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getOption(string $name, mixed $default = null): mixed
    {
        return $this->options[$name] ?? $default;
    }

}