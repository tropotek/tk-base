<?php
namespace Bs;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Page extends \Tk\Controller\Page
{

    /**
     * @param string $templatePath
     */
    public function __construct($templatePath = '')
    {
        if (!$templatePath)
            $templatePath = $this->makeDefaultTemplatePath();
        parent::__construct($templatePath);
    }

    /**
     * Create the default template path using the url role if available (see Config)
     *
     * @return string
     */
    protected function makeDefaultTemplatePath()
    {
        $urlRole = \Bs\Uri::create()->getRoleType($this->getConfig()->getAvailableUserRoleTypes());
        if (!$urlRole) $urlRole = 'public';
        return $this->getConfig()->getSitePath() . $this->getConfig()->get('template.'.$urlRole);
    }

    /**
     * Get the currently logged in user
     *
     * @return \Bs\Db\User
     */
    public function getUser()
    {
        return $this->getConfig()->getUser();
    }

    /**
     * Get the global config object.
     *
     * @return \Bs\Config
     */
    public function getConfig()
    {
        return \Bs\Config::getInstance();
    }

}