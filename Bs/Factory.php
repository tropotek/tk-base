<?php
namespace Bs;

use Bs\Db\UserInterface;
use Dom\Mvc\Loader;
use Dom\Mvc\Modifier;
use Symfony\Component\Console\Application;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Tk\Auth\Adapter\AdapterInterface;
use Tk\Auth\Auth;
use Tk\Mail\CurlyMessage;

class Factory extends \Tk\Factory
{

    public function initEventDispatcher(): ?EventDispatcher
    {
        if ($this->getEventDispatcher()) {
            new Dispatch($this->getEventDispatcher());
        }
        return $this->getEventDispatcher();
    }

    public function getConsole(): Application
    {
        if (!$this->has('console')) {
            $app = parent::getConsole();
            $this->set('console', $app);
        }
        return $this->get('console');
    }

    public function getAuthController(): Auth
    {
        if (!$this->has('authController')) {
            $auth = new Auth(new \Tk\Auth\Storage\SessionStorage($this->getSession()));
            $this->set('authController', $auth);
        }
        return $this->get('authController');
    }

    /**
     * This is the default Authentication adapter
     * Override this method in your own site's Factory object
     */
    public function getAuthAdapter(): AdapterInterface
    {
        return $this->get('authAdapter', null);
    }

    /**
     * Return a User object or record that is located from the Auth's getIdentity() method
     * Override this method in your own site's Factory object
     */
    public function getAuthUser(): ?UserInterface
    {
        return $this->get('authUser', null);
    }

    // Page/Template Methods

    public function getPublicPage(): Page
    {
        return $this->createPage($this->getSystem()->makePath($this->getConfig()->get('path.template.public')));
    }

    public function getUserPage(): Page
    {
        return $this->createPage($this->getSystem()->makePath($this->getConfig()->get('path.template.user')));
    }

    public function getAdminPage(): Page
    {
        return $this->createPage($this->getSystem()->makePath($this->getConfig()->get('path.template.admin')));
    }

    public function getMaintenancePage(): Page
    {
        return $this->createPage($this->getSystem()->makePath($this->getConfig()->get('path.template.maintenance')));
    }

    public function getLoginPage(): Page
    {
        return $this->createPage($this->getSystem()->makePath($this->getConfig()->get('path.template.login')));
    }

    public function createPage($templatePath, callable $onCreate = null): Page
    {
        $page = Page::create($templatePath);
        if ($onCreate) {
            call_user_func_array($onCreate, [$page]);
        }
        return $page;
    }

    public function getTemplateModifier(): Modifier
    {
        if (!$this->get('templateModifier')) {
            $dm = new Modifier();

            if (class_exists('ScssPhp\ScssPhp\Compiler')) {
                $vars = [
                    'baseUrl' => $this->getConfig()->getBaseUrl(),
                    'dataUrl' => $this->getSystem()->makeUrl($this->getConfig()->getDataPath())
                ];
                $scss = new Modifier\Scss($this->getConfig()->getBasePath(), $this->getConfig()->getBaseUrl(), $this->getConfig()->getCachePath(), $vars);
                $scss->setCompress(true);
                $scss->setCacheEnabled(!$this->getSystem()->isRefreshCacheRequest());
                $scss->setCacheTimeout(\Tk\Date::DAY*14);
                $dm->addFilter('scss', $scss);
            }

            $dm->addFilter('urlPath', new Modifier\UrlPath($this->getConfig()->getBaseUrl()));
            $dm->addFilter('jsLast', new Modifier\JsLast());
            if ($this->getConfig()->isDebug()) {
                $dm->addFilter('pageBytes', new Modifier\PageBytes($this->getConfig()->getBasePath()));
            }

            $this->set('templateModifier', $dm);
        }
        return $this->get('templateModifier');
    }

    public function getTemplateLoader(): ?Loader
    {
        if (!$this->has('templateLoader')) {
            $loader = new Loader($this->getEventDispatcher());
            $path = $this->getConfig()->getTemplatePath() . '/templates';
            $loader->addAdapter(new Loader\DefaultAdapter());
            $loader->addAdapter(new Loader\ClassPathAdapter($path));
            $this->set('templateLoader', $loader);
        }
        return $this->get('templateLoader');
    }

    /**
     * @param string $template (optional) If no param supplied then the system default template is used
     */
    public function createMessage(string $template = ''): CurlyMessage
    {
        if (empty($template)) {
            $tplPath = $this->getSystem()->makePath($this->getConfig()->get('system.mail.template'));
            if (is_file($tplPath)) {
                $template = file_get_contents($tplPath);
                if (!$template) {
                    \Tk\log::warning('Template file not found, using default template: ' . $tplPath);
                    $template = '{content}';
                }
            }
        }

        $message = \Tk\Mail\CurlyMessage::create($template);
        $message->setFrom($this->getRegistry()->get('system.email'));
        $message->setReplyTo($this->getRegistry()->get('system.email'));
        $message->set('sig', $this->getRegistry()->get('site.email.sig', ''));

        return $message;
    }
}