<?php
namespace Bs\Listener;

use Tk\Event\Subscriber;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class PageTemplateHandler implements Subscriber
{

    /**
     * @param \Tk\Event\Event $event
     * @deprecated Update Your Config and setup a controller getPageTemplatePath() method
     */
    public function setPageTemplatePath(\Tk\Event\Event $event)
    {
        /** @var \Bs\Controller\Iface $controller */
        $controller = $event->get('controller');
        $config = \Bs\Config::getInstance();

        // ---------------- deprecated  ---------------------
        // Deprecated in favor of using the $controller->getTemplatePath() function
        if ($config->getRequest()->getAttribute('role')) {
            $role = $config->getRequest()->getAttribute('role');
            if (is_array($role)) $role = current($role);
            $templatePath = $config->getSitePath() . $config['template.' . $role];
            $config->set('deprecated.usingPageObject', true);
            $controller->getPage()->setTemplatePath($templatePath);
        }
        //-----------------------------------------------------

    }


    /**
     * @param \Tk\Event\Event $event
     */
    public function showPage(\Tk\Event\Event $event)
    {
        $config = \Bs\Config::getInstance();
        /** @var \Bs\Controller\Iface $controller */
        $controller = $event->get('controller');
        $template = $controller->getPage()->getTemplate();

        if ($this->getConfig()->get('site.meta.keywords')) {
            $template->appendMetaTag('keywords', $this->getConfig()->get('site.meta.keywords'));
        }
        if ($this->getConfig()->get('site.meta.description')) {
            $template->appendMetaTag('description', $this->getConfig()->get('site.meta.description'));
        }

        if ($this->getConfig()->get('site.global.js')) {
            $template->appendJs($this->getConfig()->get('site.global.js'));
        }
        if ($this->getConfig()->get('site.global.css')) {
            $template->appendCss($this->getConfig()->get('site.global.css'));
        }

//        $template->appendMetaTag('tk-author', 'http://www.tropotek.com/', $template->getTitleElement());
//        $template->appendMetaTag('tk-project', 'tk2uni', $template->getTitleElement());
//        $template->appendMetaTag('tk-version', '1.0', $template->getTitleElement());

        if ($this->getConfig()->get('site.title')) {
//            $template->setAttr('siteTitle', 'title', $this->getConfig()->get('site.title'));
//            $template->setAttr('siteName', 'title', $this->getConfig()->get('site.title'));
            $template->insertText($config->get('template.var.page.site-title'), $this->getConfig()->get('site.title'));
            $template->setTitleText(trim($template->getTitleText() . ' - ' . $this->getConfig()->get('site.title'), '- '));
        }

        // TODO: create a listener for this????
        $siteUrl = $this->getConfig()->getSiteUrl();
        $dataUrl = $this->getConfig()->getDataUrl();
        $templateUrl = $this->getConfig()->getTemplateUrl();

        $js = <<<JS
var config = {
  siteUrl : '$siteUrl',
  dataUrl : '$dataUrl',
  templateUrl: '$templateUrl',
  jquery: {
    dateFormat: 'dd/mm/yy'    
  },
  bootstrap: {
    dateFormat: 'dd/mm/yyyy'    
  }
};
JS;
        $template->appendJs($js, array('data-jsl-priority' => -1000));

        // Set page title
        if ($controller->getPageTitle()) {
            $template->setTitleText(trim($controller->getPageTitle() . ' - ' . $template->getTitleText(), '- '));
            $template->insertText($config->get('template.var.page.page-header'), $controller->getPageTitle());
            $template->setChoice($config->get('template.var.page.page-header'));
        }

        if ($this->getConfig()->isDebug()) {
            $template->setTitleText(trim('DEBUG: ' . $template->getTitleText(), '- '));
            $template->setChoice('debug');
        }

        // ---- tk-base specific calls ----

        if (\Tk\AlertCollection::hasMessages()) {
            $template->insertTemplate($config->get('template.var.page.alerts'), \Tk\AlertCollection::getInstance()->show());
            $template->setChoice($config->get('template.var.page.alerts'));
        }

        if ($this->getConfig()->getUser()) {
            $template->insertText($config->get('template.var.page.user-name'), $this->getConfig()->getUser()->name);
            $template->insertText($config->get('template.var.page.username'), $this->getConfig()->getUser()->username);
            $template->setAttr($config->get('template.var.page.user-url'), 'href', $this->getConfig()->getUserHomeUrl());
            $template->setChoice($config->get('template.var.page.logout'));
        } else {
            $template->setChoice($config->get('template.var.page.login'));
        }

    }

    /**
     * @return \Bs\Config
     */
    public function getConfig()
    {
        return \Bs\Config::getInstance();
    }


    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     *
     * @api
     */
    public static function getSubscribedEvents()
    {
        return array(
            \Tk\PageEvents::PAGE_INIT => 'setPageTemplatePath',         // Deprecated
            \Tk\PageEvents::CONTROLLER_SHOW => 'showPage'
        );
    }

}