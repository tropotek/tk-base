<?php
namespace Bs;

use Bs\Ui\Crumbs;
use Composer\Autoload\ClassLoader;
use Dom\Modifier;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Application;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\Routing\Generator\CompiledUrlGenerator;
use Symfony\Component\Routing\Loader\Configurator\CollectionConfigurator;
use Symfony\Component\Routing\Matcher\CompiledUrlMatcher;
use Symfony\Component\Routing\Matcher\Dumper\CompiledUrlMatcherDumper;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Tk\Auth\Adapter\AdapterInterface;
use Tk\Auth\Adapter\DbTable;
use Tk\Auth\Auth;
use Tk\Cache\Adapter\Filesystem;
use Tk\Cache\Cache;
use Tk\Collection;
use Tk\Config;
use Tk\ConfigLoader;
use Tk\Cookie;
use Tk\Log;
use Tk\Logger\ErrorLog;
use Tk\Logger\SessionLog;
use Tk\Logger\StreamLog;
use Tk\Mail\CurlyMessage;
use Tk\Mail\Gateway;
use Tk\System;
use Tk\Uri;

class Factory extends Collection
{
    protected static mixed $_instance = null;


    public static function instance(): static
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new static();
        }
        return self::$_instance;
    }

    public function getConfig(): Config
    {
        return Config::instance();
    }

    public function getRegistry(): Registry
    {
        return Registry::instance();
    }

    public function getBootstrap(): Bootstrap
    {
        if (!$this->has('bootstrap')) {
            $bootstrap = new Bootstrap();
            $this->set('bootstrap', $bootstrap);
        }
        return $this->get('bootstrap');
    }

    public function getFrontController(): HttpKernel
    {
        if (!$this->has('frontController')) {
            $frontController = new HttpKernel(
                $this->getEventDispatcher(),
                $this->getControllerResolver(),
                $this->getRequestStack(),
                $this->getArgumentResolver()
            );
            $this->set('frontController', $frontController);
        }
        return $this->get('frontController');
    }

    /**
     * setup DB based session object
     */
    public function initSession(): ?\Tk\Db\Session
    {
        if (!$this->has('session')) {
            session_name('sn_' . md5(Config::getBaseUrl()));
            // init DB session if enabled
            if ($this->getConfig()->get('session.db_enable', false)) {
                \Tk\Db\Session::instance();
            }
            session_start();

            $_SESSION[\Tk\Db\Session::SID_IP]    = System::getClientIp();
            $_SESSION[\Tk\Db\Session::SID_AGENT] = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $_SESSION['_session.id']             = session_id();
            SessionLog::clearLog();

            $this->set('session', null);
        }
        return $this->get('session');
    }

    public function getCookie(): Cookie
    {
        if (!$this->has('cookie')) {
            $cookie = new Cookie();
            $this->set('cookie', $cookie);
        }
        return $this->get('cookie');
    }

    public function getRequest(): Request
    {
        if (!$this->has('request')) {
            $request = Request::createFromGlobals();
            $request->setSession(new Session());
            $this->set('request', $request);
        }
        return $this->get('request');
    }

    public function getRequestStack(): RequestStack
    {
        if (!$this->has('requestStack')) {
            $requestStack = new RequestStack();
            $this->set('requestStack', $requestStack);
        }
        return $this->get('requestStack');
    }

    public function getCompiledRoutes(): array
    {
        // Setup Routes and cache results.
        // Use `<Ctrl>+<Shift>+R` ro refresh the routing cache
        $systemCache = new Cache(new Filesystem(Config::makePath($this->getConfig()->get('path.cache'))));
        if (!($compiledRoutes = $systemCache->fetch('compiledRoutes')) || System::isRefreshCacheRequest()) {
            ConfigLoader::create()->loadConfigs(new CollectionConfigurator($this->getRouteCollection(), 'routes'), 'routes.php');
            $compiledRoutes = (new CompiledUrlMatcherDumper($this->getRouteCollection()))->getCompiledRoutes();
            $systemCache->store('compiledRoutes', $compiledRoutes, 60*60*24*5);
        }
        return $compiledRoutes;
    }

    public function getRouteCollection(): RouteCollection
    {
        if (!$this->has('routeCollection')) {
            $routeCollection = new RouteCollection();
            $this->set('routeCollection', $routeCollection);
        }
        return $this->get('routeCollection');
    }

    public function getRouteMatcher(): CompiledUrlMatcher
    {
        if (!$this->has('routeMatcher')) {
            $context = new RequestContext();
            $matcher = new CompiledUrlMatcher($this->getCompiledRoutes(), $context);
            $this->set('routeMatcher', $matcher);
            $this->set('routeContext', $context);
        }
        return $this->get('routeMatcher');
    }

    /**
     *  For generating URLs from routes
     *  $generator = new Routing\Generator\UrlGenerator($routes, $context);
     *  echo $generator->generate(
     *      'hello',
     *      ['name' => 'Fabien'],
     *      UrlGeneratorInterface::ABSOLUTE_URL
     *  );
     *   outputs something like http://example.com/somewhere/hello/Fabien
     */
    public function getRouteGenerator(): CompiledUrlGenerator
    {
        if (!$this->has('routeGenerator')) {
            $generator = new CompiledUrlGenerator($this->getCompiledRoutes(), $this->get('routeContext'));
            $this->set('routeGenerator', $generator);
        }
        return $this->get('routeGenerator');
    }

    public function getControllerResolver(): ControllerResolver
    {
        // todo: move to FrontController
        if (!$this->has('controllerResolver')) {
            $controllerResolver = new ControllerResolver();
            $this->set('controllerResolver', $controllerResolver);
        }
        return $this->get('controllerResolver');
    }

    public function getArgumentResolver(): ArgumentResolver
    {
        // todo: move to FrontController
        if (!$this->has('argumentResolver')) {
            $argumentResolver = new ArgumentResolver();
            $this->set('argumentResolver', $argumentResolver);
        }
        return $this->get('argumentResolver');
    }

    public function initLogger(): void
    {
        // Init \Tk\Log
        $logLevel = $this->getConfig()->get('log.logLevel', LogLevel::DEBUG);
        Log::setEnableNoLog($this->getConfig()->get('log.enableNoLog', true));
        $logfile = $this->getConfig()->get('php.error_log', ini_get('error_log'));
        if (is_writable($logfile)) {
            Log::addHandler(new StreamLog($logfile, $logLevel));
        } else {
            Log::addHandler(new ErrorLog($logLevel));
        }
    }

    /**
     * Get the composer Class Loader object returned from the autoloader in the _prepend.php file
     */
    public function getComposerLoader(): ?ClassLoader
    {
        return $this->get('composerLoader');
    }

    /**
     * @see https://symfony.com/doc/current/reference/events.html
     */
    public function getEventDispatcher(): ?EventDispatcher
    {
        // todo: move to FrontController, keep method save in factory
        if (!$this->has('eventDispatcher')) {
            $dispatcher = new EventDispatcher();
            $this->set('eventDispatcher', $dispatcher);
        }
        return $this->get('eventDispatcher');
    }

    public function initEventDispatcher(): ?EventDispatcher
    {
        // todo: move to bootstrap
        if ($this->getEventDispatcher()) {
            new Dispatch($this->getEventDispatcher());
        }
        return $this->getEventDispatcher();
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
     * This is the default Authentication adapter
     * Override this method in your own site's Factory object
     */
    public function getAuthAdapter(): AdapterInterface
    {
        if (!$this->has('authAdapter')) {
            $adapter = new DbTable('auth');
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
                    'baseUrl' => Config::getBaseUrl(),
                    'dataUrl' => Config::makeUrl(Config::getDataPath())
                ];
                $scss = new Modifier\Scss(
                    Config::getBasePath(),
                    Config::getBaseUrl(),
                    Config::makePath(Config::getCachePath()),
                    $vars
                );
                $scss->setCompress(true);
                $scss->setCacheEnabled(!System::isRefreshCacheRequest());
                $scss->setCacheTimeout(\Tk\Date::DAY*14);
                $dm->addFilter('scss', $scss);
            }

            $dm->addFilter('urlPath', new Modifier\UrlPath(Config::getBaseUrl()));
            // TODO: see if we can live without this, would rather leave it to the template
            //       we will lose control over where we add scripts but it may not matter.
            //$dm->addFilter('jsLast', new Modifier\JsLast());
            if (Config::isDebug()) {
                $dm->addFilter('pageBytes', new Modifier\PageBytes(Config::getBasePath()));
            }

            $this->set('templateModifier', $dm);
        }
        return $this->get('templateModifier');
    }

    /**
     * @param string $template (optional) If no param supplied then the system default template is used
     */
    public function createMessage(string $template = ''): CurlyMessage
    {
        if (empty($template)) {
            $tplPath = Config::makePath($this->getConfig()->get('system.mail.template'));
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
     * get the mail gateway to send emails
     *
     * @todo Look into making the gateway send method static with an init function to be called in the Bootstrap
     */
    public function getMailGateway(): ?Gateway
    {
        // move init to bootstrap keep method
        if (!$this->has('mailGateway')) {
            $params = $this->getConfig()->all();
            if (!System::isCli()) {
                $params['clientIp'] = System::getClientIp();
                $params['hostname'] = Config::getHostname();
                $params['referer']  = $_SERVER['HTTP_REFERER'] ?? '';
            }
            $gateway = new \Tk\Mail\Gateway($params);
            //$gateway->setDispatcher($this->getEventDispatcher());
            $this->set('mailGateway', $gateway);
        }
        return $this->get('mailGateway');
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
                if (\Au\Auth::getAuthUser()) {
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
        $thisUrl = Uri::create();
        $crumbUrl = Uri::create($this->getCrumbs()->getBackUrl());
        if ($crumbUrl->toString() != $thisUrl->toString()) {
            return $crumbUrl;
        }
        if (\Au\Auth::getAuthUser()) {
            return \Au\Auth::getAuthUser()->getHomeUrl();
        }
        return Uri::create('/');
    }

    public function getConsole(): Application
    {
        if (!$this->has('console')) {
            $name = '';
            if (System::getComposerJson()) {
                $sys = System::getComposerJson();
                $name = $sys['name'] ?? '';
            }
            $app = new Application($name, System::getVersion());
            $app->setDispatcher($this->getEventDispatcher());

            // Setup Global Console Commands
            $app->add(new \Bs\Console\Password());
            $app->add(new \Bs\Console\Command\CleanData());
            $app->add(new \Bs\Console\Command\Upgrade());
            $app->add(new \Bs\Console\Command\Maintenance());
            $app->add(new \Bs\Console\Command\DbBackup());
            $app->add(new \Bs\Console\Command\Migrate());
            if (Config::isDev()) {
                $app->add(new \Bs\Console\Command\Debug());
                $app->add(new \Bs\Console\Command\Mirror());
                // todo refactor these for the lib updates
//                $app->add(new \Bs\Console\Command\MakeModel());
//                $app->add(new \Bs\Console\Command\MakeMapper());
//                $app->add(new \Bs\Console\Command\MakeTable());
//                $app->add(new \Bs\Console\Command\MakeForm());
//                $app->add(new \Bs\Console\Command\MakeManager());
//                $app->add(new \Bs\Console\Command\MakeEdit());
//                $app->add(new \Bs\Console\Command\MakeAll());
            }

            $this->set('console', $app);
        }
        return $this->get('console');
    }
}