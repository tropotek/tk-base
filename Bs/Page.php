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
     * Set the page heading, should be set from main controller
     *
     * @return \Dom\Template
     * @throws \Exception
     */
    public function show()
    {
        $template = parent::show();

        // TODO: Move to a listener renderer

        if (\Tk\AlertCollection::hasMessages()) {
            $template->insertTemplate('alerts', \Tk\AlertCollection::getInstance()->show());
            $template->setChoice('alerts');
        }

        if ($this->getUser()) {
            $template->setChoice('logout');
            $template->insertText('username', $this->getUser()->name);
            $template->setAttr('user-home', 'href', $this->getUser()->getHomeUrl());
            $template->setAttr('userUrl', 'href', $this->getUser()->getHomeUrl());
        } else {
            $template->setChoice('login');
        }

        return $template;
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