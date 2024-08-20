<?php
namespace Bs;

class PagePhp extends PageInterface
{
    /**
     * Return the rendered page with all content
     * This will be called by the page handler to get the final page HTML
     */
    public function getHtml(): string
    {
        $page = $this;
        ob_start();
        if (is_file($this->getTemplatePath())) {
            include $this->getTemplatePath();
            return ob_get_clean();
        }
        return '';
    }

}