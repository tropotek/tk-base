<?php
namespace Bs;

use Bs\Db\UserInterface;
use Bs\Mvc\Page;
use Bs\Mvc\PageDomInterface;
use Bs\Mvc\PageInterface;
use Bs\Mvc\PagePhp;
use Bs\Ui\Breadcrumbs;
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
use Tk\Cache\Cache;
use Tk\Collection;
use Tk\Config;
use Tk\ConfigLoader;
use Tk\Cookie;
use Tk\Date;
use Tk\FileUtil;
use Tk\Log;
use Tk\Logger\ErrorLog;
use Tk\Logger\StreamLog;
use Tk\Mail\CurlyMessage;
use Tk\Mail\Mailer;
use Tk\Mail\Message;
use Tk\Path;
use Tk\Db\MySqlSession;
use Tk\System;
use Tk\Uri;

/**
 * NOTE: The methods in the factory must be called via `Factory::instance()->...` (avoid using static methods).
 * Object methods can be overridden with a new Factory object inherited by a parent (eg: \App\Factory).
 * This is the purpose of Factory objects is to facilitate customisations of
 * the base default \Bs\Mvc lib behaviour.
 *
 * The initial call to the instance() method determines the Factory object used in preceding instance() calls.
 * See the `/_prepend.php` file, using `\App\Factory::instance()` as the initial instance() call, ensures
 * that further calls to `\Tk\Factory::instance()` will return/use the `\App\Factory` object instance.
 *
 */
class Factory extends Collection
{
    protected static mixed $_instance = null;

    final function __construct(array $items = [])
    {
        parent::__construct($items);
    }

    public static function instance(): static
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new static();
        }
        return self::$_instance;
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
     * setup/get DB based session object
     * First call to this method will create a new session object
     * Subsequent calls will return the same session object
     */
    public function getSession(): ?\Tk\Session
    {
        if (!$this->has('session')) {

            $handler = null;
            if (!System::isCli() && Config::getValue('session.db_enable', false)) {
                // note DB must be initialized before the session is started
                $handler = new MySqlSession();
            }

            session_cache_limiter('nocache');
            session_name('sn_' . md5(Config::getBaseUrl()));
            $session = \Tk\Session::instance($handler);

            $this->set('session', $session);
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

    public function getCompiledRoutes(bool $refresh = false): array
    {
        // Setup Routes and cache results.
        // Use `<Ctrl>+<Shift>+R` to refresh the routing cache
        $systemCache = $this->getCache();
        $compiledRoutes = $systemCache->fetch('compiledRoutes');
        if ($refresh || !is_array($compiledRoutes) || System::isRefreshCacheRequest()) {
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
        if (!$this->has('controllerResolver')) {
            $controllerResolver = new ControllerResolver();
            $this->set('controllerResolver', $controllerResolver);
        }
        return $this->get('controllerResolver');
    }

    public function getArgumentResolver(): ArgumentResolver
    {
        if (!$this->has('argumentResolver')) {
            $argumentResolver = new ArgumentResolver();
            $this->set('argumentResolver', $argumentResolver);
        }
        return $this->get('argumentResolver');
    }

    public function initLogger(): void
    {
        // Init \Tk\Log
        $logLevel = Config::getValue('log.logLevel', LogLevel::DEBUG);
        // allow uri query string with no_log to stop logging
        Log::setEnableNoLog(Config::getValue('log.enableNoLog', true));
        $logfile = Config::getValue('php.error_log', ini_get('error_log'));
        if (is_writable($logfile)) {
            Log::addLogger(new StreamLog($logfile, $logLevel));
        } else {
            Log::addLogger(new ErrorLog($logLevel));
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
        if (!$this->has('eventDispatcher')) {
            $dispatcher = new EventDispatcher();
            $this->set('eventDispatcher', $dispatcher);
        }
        return $this->get('eventDispatcher');
    }

    /**
     * Init site-specific event listeners
     */
    public function initEventDispatcher(): ?EventDispatcher
    {
        if (!$this->has('eventDispatcher')) {
            new Listeners($this->getEventDispatcher());
        }
        return $this->getEventDispatcher();
    }

    public function getAuthController(?AdapterInterface $adapter = null): Auth
    {
        if (!$this->has('authController')) {
            $auth = new Auth(new \Tk\Auth\Storage\SessionStorage(), $this->getAuthAdapter());
            $this->set('authController', $auth);
        }

        $auth = $this->get('authController');
        $auth->setAdapter($adapter);
        return $auth;
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
                    'dataUrl' => Uri::createDataUri('/')->getPath()
                ];
                $scss = new Modifier\Scss($vars, Path::createDataPath('/cache'));
                $scss->setCompress(true);
                $scss->setCacheTimeout(\Tk\Date::DAY*14);
                $scss->setPerPageCache(false);
                $dm->addModifier($scss);
            }

            Modifier\UrlPath::$IS_DEBUG = Config::isDev();
            $dm->addModifier(new Modifier\UrlPath(Config::getBaseUrl()));

            if (Config::isDev()) {
                $dm->addModifier(new Modifier\PageBytes(Config::getBasePath()));
            } else {
                $dm->addModifier(new Modifier\ClearComments());
            }

            $this->set('templateModifier', $dm);
        }
        return $this->get('templateModifier');
    }

    /**
     * @param string $template (optional) If no param supplied then the system default template is used
     */
    public function createMailMessage(string $content = '', string $template = ''): CurlyMessage
    {
        if (empty($template)) {
            $tplPath = Path::create(Config::getValue('system.mail.template'));
            if (is_file($tplPath)) {
                $template = file_get_contents($tplPath);
            }
            if (!$template) {
                \Tk\Log::warning('Template file not found, using default template: ' . $tplPath);
                $template = '{content}';
            }
        }

        // replace the {content} with the supplied content template
        $template = str_replace('{content}', $content, $template);

        $siteEmail = Message::joinEmail(Registry::getSiteEmail(), Registry::getSiteName());
        $siteFrom = Message::joinEmail(Config::getValue('email.from', Registry::getSiteEmail()), Registry::getSiteName());

        $message = new \Tk\Mail\CurlyMessage($template);
        $message->setFrom($siteFrom);
        $message->setReplyTo($siteEmail);
        $message->addBcc($siteEmail);
        $message->set('sig', Registry::getValue('site.email.sig', ''));

        $message->set('siteName', Registry::getSiteName());
        $message->set('siteEmail', Registry::getSiteEmail());
        $message->set('date', Date::create()->format(Date::FORMAT_LONG_DATE));
        $message->set('dateTime', Date::create()->format(Date::FORMAT_LONG_DATETIME));

        return $message;
    }

    public function initMailGateway(): ?Mailer
    {
        $params = Config::instance()->all();
        if (!System::isCli()) {
            $params['clientIp'] = System::getClientIp();
            $params['hostname'] = Config::getHostname();
            $params['referer']  = $_SERVER['HTTP_REFERER'] ?? '';
        }
        return \Tk\Mail\Mailer::instance($params);
    }

    public function initBreadcrumbs(): Breadcrumbs
    {
        $crumbs = Breadcrumbs::init();
        if (\Bs\Auth::getAuthUser()) {
            Breadcrumbs::setHome('/dashboard', '<i class="fa fa-home"></i>');
        } else {
            Breadcrumbs::setHome('/', '<i class="fa fa-home"></i>');
        }
        return $crumbs;
    }

    /**
     * Override this in the site Factory object to create a new user
     */
    public function createNewUser(string $username, string $email, string $password, int $perms = 0, string $type = 'staff'): ?UserInterface
    {
        Log::warning('Factory::createNewUser() not implemented');
        return null;
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
            $app->add(new \Bs\Console\Install());
            $app->add(new \Bs\Console\InstallOld());
            $app->add(new \Bs\Console\Deploy());
            $app->add(new \Bs\Console\AppKey());
            $app->add(new \Bs\Console\Upgrade());

            $app->add(new \Bs\Console\Password());
            $app->add(new \Bs\Console\CreateAdmin());
            $app->add(new \Bs\Console\CleanData());
            $app->add(new \Bs\Console\Maintenance());
            $app->add(new \Bs\Console\DbBackup());
            $app->add(new \Bs\Console\Migrate());
            $app->add(new \Bs\Console\PurgeCache());

            if (Config::isDev()) {
                $app->add(new \Bs\Console\Mirror());
                $app->add(new \Bs\Console\MirrorData());
                // model generator commands
                $app->add(new \Bs\Console\Generator\MakeModel());
                $app->add(new \Bs\Console\Generator\MakeManager());
                $app->add(new \Bs\Console\Generator\MakeTable());
                $app->add(new \Bs\Console\Generator\MakeEdit());
                $app->add(new \Bs\Console\Generator\MakeForm());
                $app->add(new \Bs\Console\Generator\MakeAll());
            }

            $this->set('console', $app);
        }
        return $this->get('console');
    }

    /**
     * @deprecated use Config functions directly
     */
    public function getConfig(): Config
    {
        return Config::instance();
    }

    /**
     * @deprecated use Registry static functions directly
     */
    public function getRegistry(): Registry
    {
        return Registry::instance();
    }

    /**
     * @deprecated use \Tk\Mail\Mailer::instance()
     */
    public function getMailGateway(): ?Mailer
    {
        return \Tk\Mail\Mailer::instance();
    }

    /**
     * @deprecated use Breadcrumbs::getBackUrl()
     */
    public function getBackUrl(): Uri
    {
        return Breadcrumbs::getBackUrl();
    }

    /**
     * get a public filesystem cache
     * Note: caches files are accessible to the web
     */
    public function getCache(): Cache
    {
        if (!$this->has('sysCache')) {
            $cache = new Cache();
            $this->set('sysCache', $cache);
        }
        return $this->get('sysCache');
    }

    public function purgeCache(): void
    {
        $this->getCache()->purge();

        // purge public cache if exists
        $publicCache = Path::createDataPath('/cache');
        if ($publicCache->isDir()) {
            FileUtil::rmdir($publicCache);
        }
    }
}
