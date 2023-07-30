<?php
namespace Bs;

use Bs\Db\User;
use Bs\Db\UserInterface;
use Bs\Db\UserMap;
use Bs\Dom\Modifier\DomAttributes;
use Bs\Ui\Crumbs;
use Dom\Mvc\Loader;
use Dom\Mvc\Modifier;
use Symfony\Component\Console\Application;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Tk\Auth\Adapter\AdapterInterface;
use Tk\Auth\Adapter\AuthUser;
use Tk\Auth\Auth;
use Tk\Mail\CurlyMessage;
use Tk\Uri;

class Factory extends \Tk\Factory
{

    protected function __construct() {
        parent::__construct();
    }

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
     * Return a User object or record that is located from the Auth's getIdentity() method
     * Override this method in your own site's Factory object
     * @return null|UserInterface|User|\App\Db\User Null if no user logged in
     */
    public function getAuthUser(): null|UserInterface|User
    {
        if (!$this->has('authUser')) {
            if ($this->getAuthController()->hasIdentity()) {
                $user = $this->getUserMap()->findByUsername($this->getAuthController()->getIdentity());
                $this->set('authUser', $user);
            }
        }
        return $this->get('authUser');
    }

    /**
     * This is the default Authentication adapter
     * Override this method in your own site's Factory object
     */
    public function getAuthAdapter(): AdapterInterface
    {
        if (!$this->has('authAdapter')) {
            //$adapter = new DbTable($this->getDb(), 'user', 'username', 'password');
            $adapter = new AuthUser($this->getUserMap());
            $this->set('authAdapter', $adapter);
        }
        return $this->get('authAdapter');
    }

    /**
     * You can select the page's template by adding `->defaults(['template' => '{public|admin|user|login|maintenance|error}'])`.
     *
     * Other options may be available if you have created new template paths in the `20-config.php` file.
     * Create a new path with `$config->set('path.template.custom', '/html/newTemplate/index.html');`
     * then add `->defaults(['template' => 'custom'])` to the route. (case-sensitive)
     */
    public function createPageFromType(string $pageType): Page
    {
        if (empty($pageType)) $pageType = Page::TEMPLATE_PUBLIC;
        return $this->createPage($this->getSystem()->makePath($this->getConfig()->get('path.template.'.$pageType)));
    }

    public function createPage(string $templatePath = ''): Page
    {
        return Page::create($templatePath);
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

            $dm->addFilter('appAttributes', new DomAttributes());
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
        $message->setFrom($this->getRegistry()->getSiteEmail());
        $message->setReplyTo($this->getRegistry()->getSiteEmail());
        $message->set('sig', $this->getRegistry()->get('site.email.sig', ''));

        return $message;
    }

    public function createUser(): User
    {
        return new User();
    }

    public function getUserMap(): UserMap
    {
        return UserMap::create();
    }

    /**
     * Get a breadcrumb object by page type
     */
    public function getCrumbs(): ?Crumbs
    {
        $type = $this->getRequest()->get('template', 'public');
        $id = 'breadcrumbs.' . $type;
        //$this->getSession()->set($id, null);
        if (!$this->has($id)) {
            $crumbs = $this->getSession()->get($id);
            if (!$crumbs instanceof Crumbs) {
                $crumbs = Crumbs::create();
                $crumbs->setHomeTitle('<i class="fa fa-home"></i>');
                if ($type == Page::TEMPLATE_ADMIN) {
                    $crumbs->setHomeUrl('/dashboard');
                }
                $crumbs->reset();
                $this->getSession()->set($id, $crumbs);
            }
            $this->set($id, $crumbs);
        }
        return $this->get($id);
    }

    public function getBackUrl(): Uri
    {
        return Uri::create($this->getCrumbs()->getBackUrl());
    }
}