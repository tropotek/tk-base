<?php
namespace Bs\Controller;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2016 Michael Mifsud
 */
abstract class Iface extends \Tk\Controller\Iface
{

    /**
     * Get a new instance of the page to display the content in.
     *
     * NOTE: This is the default, override to load your own page objects
     *
     * @return \Tk\Controller\Page|\Bs\Page
     * @todo: this is very confusing and hard to trace,  we need a better method to instantiate a page object
     */
    public function getPage()
    {
        if (!$this->page) {
            $this->page = $this->getConfig()->getPage();
            $this->page->setController($this);
        }
        return parent::getPage();
    }

    /**
     * @return string
     */
    public function getDefaultTitle()
    {
        return $this->getConfig()->makePageTitle();
    }

    /**
     * @return \Tk\Crumbs
     */
    public function getCrumbs()
    {
        return $this->getConfig()->getCrumbs();
    }

    /**
     * DomTemplate magic method example
     *
     * @return \Dom\Template
     */
    public function __makeTemplate()
    {
        $html = <<<HTML
<div></div>
HTML;
        $tpl = \Dom\Loader::load($html);
        // OR FOR A FILE
        //$tpl = \Dom\Loader::loadFile($this->getTemplatePath().'/public.xtpl');
        return $tpl;
    }

}