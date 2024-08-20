<?php
namespace Bs;

use Bs\Db\User;
use Bs\Dom\Modifier\DomAttributes;
use Bs\Ui\Crumbs;
use Dom\Loader;
use Dom\Modifier;
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
            $auth = new Auth(new \Tk\Auth\Storage\SessionStorage());
            $this->set('authController', $auth);
        }
        return $this->get('authController');
    }

    /**
     * Return a User object or record that is located from the Auth's getIdentity() method
     * Override this method in your own site's Factory object
     * @return null|User Null if no user logged in
     */
    public function getAuthUser(): null|User
    {
        if (!$this->has('authUser')) {
            if ($this->getAuthController()->hasIdentity()) {
                $user = User::findByUsername($this->getAuthController()->getIdentity());
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
            $adapter = new AuthUser();
            $this->set('authAdapter', $adapter);
        }
        return $this->get('authAdapter');
    }

    public function initPage(string $templatePath = ''): PageInterface
    {
        $page = $this->get('pageRenderer');
        if (is_null($page)) {
            if (str_ends_with($templatePath, '.php')) {
                $page = new PagePhp($templatePath);
            } else {
                $page = $this->createDomPage($templatePath);
                $page->setDomModifier($this->getTemplateModifier());
            }
            $this->set('pageRenderer', $page);
        }
        return $page;
    }

    public function createDomPage(string $templatePath = ''): PageDomInterface
    {
        return new Page($templatePath);
    }

    public function getPage(): null|PageInterface|PageDomInterface
    {
        return $this->get('pageRenderer');
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
            $loader = new Loader();
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

    /**
     * @deprecated use `\Bs\User::create()`
     */
    public function createUser(): User
    {
        return new User::$USER_CLASS();
    }

    /**
     * @deprecated
     */
    public function getUserMap(): null
    {
        return null;
    }

    /**
     * Get a breadcrumb object by page type
     */
    public function getCrumbs(): ?Crumbs
    {
        $type = $_GET['template'] ?? 'public';
        $id = 'breadcrumbs.' . $type;

        if (!$this->has($id)) {
            $crumbs = $_SESSION[$id] ?? null;
            if (!$crumbs instanceof Crumbs) {
                $crumbs = Crumbs::create();
                $crumbs->setHomeTitle('<i class="fa fa-home"></i>');
                if ($type == Page::TEMPLATE_ADMIN) {
                    $crumbs->setHomeUrl('/dashboard');
                }
                $crumbs->reset();
                $_SESSION[$id] = $crumbs;
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