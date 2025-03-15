<?php
namespace Bs\Mvc;

use Bs\Traits\SystemTrait;
use Bs\Ui\Breadcrumbs;
use Tk\Uri;

abstract class PageInterface
{
    use SystemTrait;

    private string $title        = '';
    private string $icon         = '';
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


    public function addContent(mixed $renderer, string $name = ''): static
    {
        $name = $name ?: 'content';
        $this->contentList[$name][] = $renderer;
        return $this;
    }

    public function getContent(string $name): array
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

    public function setTitle(string $title, ?string $icon = null): static
    {
        $this->title = $title;
        if (!is_null($icon)) {
            $this->setIcon($icon);
        }
        Breadcrumbs::pushCrumb(Uri::create(), $title);
        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): static
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

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function setIcon(string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

}