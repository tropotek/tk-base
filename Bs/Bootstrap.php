<?php
namespace Bs;

use Bs\Listener\StartupHandler;
use Dom\Template;
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
        $config = Config::instance();

        // Apply all php config settings to php
        foreach (Config::getGroup('php', true) as $k => $v) {
            @ini_set($k, $v);
        }

        // make app directories if not exists
        FileUtil::mkdir(Path::createPrivatePath());
        FileUtil::mkdir(Path::createTempPath());
        FileUtil::mkdir(Path::createCachePath());

        if ($config->has('db.mysql')) {
            Db::connect($config->get('db.mysql', ''));
        }
        TextEncrypt::$encryptKey = $config->get('system.encrypt', '');

        StartupHandler::$PARAMS = $config->get('site.log.params', StartupHandler::LOG_ALL);

        Factory::instance()->initLogger();

        ErrorHandler::instance();

        VarDump::instance();

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