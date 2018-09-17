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
        if (!$templatePath)    // set
            $templatePath = $this->makeDefaultTemplatePath();

        parent::__construct($templatePath);

//        // TODO: FIX THIS ! Could possibly add more than one if more than one page instance is created .... ???? !!!!!
//        $this->getConfig()->getDomLoader()->addAdapter(new \Dom\Loader\Adapter\ClassPath(
//            dirname($templatePath).'/xtpl',
//            $this->getConfig()->get('template.xtpl.ext'),
//            false
//        ));
    }

    /**
     * Create the default template path using the url role if available (see Config)
     *
     *  // Theme Path
     *  $config['system.theme.path'] = $config['system.template.path'] . '/cube/admin.html';
     *
     * @return string
     * @todo This should be the site default
     */
//    protected function makeDefaultTemplatePath()
//    {
//        return $this->getConfig()->getSitePath() . $this->getConfig()->get('system.theme.path');
//    }

    /**
     * Create the default template path using the url role if available (see Config)
     *
     * @return string
     * @todo Would like to deprecate this method to remove the role.type value from the internals of the system
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