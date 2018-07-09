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
     * @return string
     */
    public function getDefaultTitle()
    {
        return $this->getConfig()->makePageTitle();
    }

    /**
     * Get the currently logged in user
     *
     * @return \Bs\Db\User|\Uni\Db\User
     */
    public function getUser()
    {
        return $this->getConfig()->getUser();
    }

    /**
     * @return \Tk\Config|\Bs\Config|\App\Config
     */
    public function getConfig()
    {
        return parent::getConfig();
    }

    /**
     * @return \Tk\Crumbs
     */
    public function getCrumbs()
    {
        return $this->getConfig()->getCrumbs();
    }

    /**
     * @return \Tk\Uri
     */
    public function getBackUrl()
    {
        return $this->getCrumbs()->getBackUrl();
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
        return \Dom\Loader::load($html);
        // OR FOR A FILE
        //return \Dom\Loader::loadFile($this->getTemplatePath().'/public.xtpl');
    }

}