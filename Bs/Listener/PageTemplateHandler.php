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
     */
    public function showPage(\Tk\Event\Event $event)
    {
        /** @var \Bs\Controller\Iface $controller */
        $controller = $event->get('controller');
        $config = \Bs\Config::getInstance();
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

        if ($this->getConfig()->get('site.title')) {
            $template->insertText($config->get('template.var.page.site-title'), $this->getConfig()->get('site.title'));
            $template->setTitleText(trim($template->getTitleText() . ' - ' . $this->getConfig()->get('site.title'), '- '));
        }

        // TODO: create a listener for this????
        $rel = \Tk\Uri::create()->getRelativePath();
        $siteUrl = $this->getConfig()->getSiteUrl();
        $dataUrl = $this->getConfig()->getDataUrl();
        $templateUrl = $this->getConfig()->getTemplateUrl();
        $role = '';
        if ($this->getConfig()->getUser()) {
            $role = $this->getConfig()->getUser()->getRole()->getType();
        }

        $js = <<<JS
var config = {
  relativePath : '$rel',
  siteUrl : '$siteUrl',
  dataUrl : '$dataUrl',
  templateUrl: '$templateUrl',
  role: '$role',
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
        if ($controller->getPageTitle() && $controller->isShowTitle()) {
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
            vd('------');
            $template->appendTemplate($config->get('template.var.page.alerts'), \Tk\AlertCollection::getInstance()->show());
            $template->setChoice($config->get('template.var.page.alerts'));
        }

        if ($this->getConfig()->getUser()) {
            $template->insertText($config->get('template.var.page.user-name'), $this->getConfig()->getUser()->name);
            $template->insertText($config->get('template.var.page.username'), $this->getConfig()->getUser()->username);
            $i = strpos($this->getConfig()->getUser()->username, '@');
            if ($i > 0) {
                $template->insertText($config->get('template.var.page.username'), substr($this->getConfig()->getUser()->username, 0, $i));
            }

            $template->setAttr($config->get('template.var.page.user-url'), 'href', $this->getConfig()->getUserHomeUrl());
            $template->setChoice($config->get('template.var.page.logout'));
            $template->addCss($template->getBodyElement(), $this->getConfig()->getUser()->getRole()->getType());
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
            \Tk\PageEvents::CONTROLLER_SHOW => 'showPage'
        );
    }

}