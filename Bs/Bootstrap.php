<?php
namespace Bs;

use Bs\Listener\StartupHandler;
use Dom\Template;
use Dotenv\Dotenv;
use Tk\Config;
use Tk\DataMap\Db\TextEncrypt;
use Tk\Debug\VarDump;
use Tk\ErrorHandler;
use Tk\FileUtil;
use Tk\Path;
use Tk\System;
use Tk\Db;
use Tk\Uri;

class Bootstrap
{

    public function init(): void
    {
        $basePath = System::discoverBasePath();
        if (is_file($basePath . '/.env')) {
            // TODO: this should be cached
            $dotenv = Dotenv::createImmutable($basePath);
            $dotenv->load(); // Load the variables
            foreach ($_ENV as $k => $v) {
                putenv("$k=$v");
            }
        }

        $config = Config::instance();

        // Apply all php config settings to php
        foreach (Config::getGroup('php', true) as $k => $v) {
            @ini_set($k, $v);
        }

        // make app directories if not exists
        FileUtil::mkdir(Path::createPrivatePath());
        FileUtil::mkdir(Path::createTempPath());
        FileUtil::mkdir(Path::createCachePath());

        TextEncrypt::$encryptKey = $config->get('system.encrypt', '');
        StartupHandler::$PARAMS = $config->get('site.log.params', StartupHandler::LOG_ALL);

        Factory::instance()->initLogger();
        ErrorHandler::instance();
        VarDump::instance();

        try {
            if ($config->has('db.mysql')) {
                Db::connect(
                    $config->get('db.mysql', ''),
                    $config->get('db.mysql.options', [])
                );
            }
        } catch (\Exception $e) {
            error_log('Database Initialization Error: ' . $e->getMessage());
        }

        Factory::instance()->getSession();
        Factory::instance()->initEventDispatcher();
        Factory::instance()->initMailGateway();

        if (System::isCli()) {
            $this->cliInit();
        } else {
            $this->httpInit();
        }
    }

    protected function httpInit(): void
    {
        /**
         * This makes our life easier when dealing with paths. Everything is relative
         * to the application root now.
         */
        chdir(Config::getBasePath());

        if (Config::isDev()) {
            Template::$ENABLE_TRACER = true;
        }
        Factory::instance()->getRequest();
        Factory::instance()->initBreadcrumbs();

    }

    protected function cliInit(): void
    {


    }

}