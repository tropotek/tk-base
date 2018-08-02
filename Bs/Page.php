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
     * @throws \Exception
     */
    public function __construct($templatePath = '')
    {
        if (!$templatePath) {   // set the default template path using the url role if available
            $urlRole = \Bs\Uri::create()->getRoleType($this->getConfig()->getAvailableUserRoleTypes());
            if (!$urlRole) $urlRole = 'public';
            $templatePath = $this->getConfig()->getSitePath() . $this->getConfig()->get('template.'.$urlRole);
        }
        parent::__construct($templatePath);

        $this->getConfig()->getDomLoader()->addAdapter(new \Dom\Loader\Adapter\ClassPath(
            dirname($templatePath).'/xtpl',
            $this->getConfig()->get('template.xtpl.ext'),
            false
        ));
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