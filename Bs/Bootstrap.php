<?php
namespace Bs;

use Bs\Listener\StartupHandler;
use Bs\Ui\Breadcrumbs;
use Dom\Template;
use Tk\Config;
use Tk\DataMap\Db\TextEncrypt;
use Tk\Debug\VarDump;
use Tk\ErrorHandler;
use Tk\FileUtil;
use Tk\Log;
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
        FileUtil::mkdir(Config::makePath(Config::getTempPath()));
        FileUtil::mkdir(Config::makePath(Config::getCachePath()));

        if ($config->has('db.mysql')) {
            Db::connect(
                $config->get('db.mysql', ''),
                $config->get('db.mysql.options', []),
            );
            if ($config->get('php.date.timezone')) {
                DB::setTimezone($config->get('php.date.timezone', 'Australia/Melbourne'));
            }
        }

        StartupHandler::$PARAMS = $config->get('site.log.params', StartupHandler::LOG_ALL);

        Factory::instance()->initLogger();

        // Init tk error handler
        ErrorHandler::instance();

        VarDump::instance();

        TextEncrypt::$encryptKey = $config->get('system.encrypt', '');

        if (Config::isDev()) {
            // Allow self-signed certs in file_get_contents in dev environment
            stream_context_set_default(["ssl" => [
                "verify_peer" => false,
                "verify_peer_name" => false,
            ]]);
        }

        Factory::instance()->initEventDispatcher();
        Factory::instance()->initMailGateway();

        Uri::$SITE_HOST = Config::getHostname();
        Uri::$BASE_PATH = Config::getBaseUrl();

        Factory::instance()->initSession();

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