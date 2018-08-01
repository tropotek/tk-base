<?php
namespace Bs;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2018 Michael Mifsud
 */
class Bootstrap
{
    /**
     * @return \Bs\Config
     */
    public static function execute()
    {
        $obj = new static();
        return $obj->init();
    }

    /**
     * This will also load dependant objects into the config, so this is the DI object for now.
     *
     * @return \Bs\Config
     * @throws \Exception
     */
    public function init()
    {
        if (version_compare(phpversion(), '5.3.0', '<')) {
            // php version must be high enough to support traits
            throw new \Exception('Your PHP5 version must be greater than 5.3.0 [Curr Ver: ' . phpversion() . ']. (Recommended: php 7.0+)');
        }

        $config = \Bs\Config::getInstance();

        // This maybe should be created in a Factory or DI Container....
        if (is_writable($config->getLogPath())) {
            if (!$config->getRequest()->has('nolog')) {
                $logger = new Logger('system');
                $handler = new StreamHandler($config->getLogPath(), $config->getLogLevel());
                $formatter = new \Tk\Log\MonologLineFormatter();
                $formatter->setScriptTime($config->getScriptTime());
                $handler->setFormatter($formatter);
                $logger->pushHandler($handler);
                $config->setLog($logger);
                \Tk\Log::getInstance($logger);
                \Dom\Template::$logger = $logger;
            }
        } else {
            error_log('Log Path not readable: ' . $config->getLogPath());
        }

        if (!$config->isDebug()) {
            ini_set('display_errors', 'Off');
            error_reporting(0);
        } else {
            \Dom\Template::$enableTracer = true;
        }

        // Init framework error handler
        \Tk\ErrorHandler::getInstance($config->getLog());

        // Initiate the default database connection
        $config->getDb();

        // Load Config with db data
        $config->replace(\Tk\Db\Data::create()->all());

        // Return if using cli (Command Line)
        if ($config->isCli()) return $config;

        // --- HTTP only bootstrapping from here ---

        // Include all URL routes
        $config->loadRoutes();

        // * Session
        $config->getSession();

        return $config;
    }


}

