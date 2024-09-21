<?php
namespace Bs;

class PagePhp extends PageInterface
{
    /**
     * Return the executed PHP file's HTML
     * Called by the PageHandler if the template file ends in '.php'
     */
    public function getHtml(): string
    {
        $page = $this;
        ob_start();
        if (is_file($this->getTemplatePath())) {
            include $this->getTemplatePath();
            return trim(ob_get_clean());
        }
        return '';
    }

}