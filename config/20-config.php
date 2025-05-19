<?php
use Tk\Config;

/**
 *
 */
return function (Config $config) {

    /**
     * Set the default templates
     */
    $config->set('path.template.public',      '/html/public.html');
    $config->set('path.template.admin',       '/html/public.html');
    $config->set('path.template.user',        '/html/public.html');
    $config->set('path.template.login',       '/html/login.html');
    $config->set('path.template.maintenance', '/html/login.html');
    $config->set('path.template.error',       '/html/login.html');

    /**
     * Validate user passwords on input
     * - Must include at least one number
     * - Must include at least one letter
     * - Must include at least one capital
     * - Must include at least one symbol
     * - must >= 8 characters
     *
     * Note: validation disabled in dev environments
     * (default: true)
     */
    $config['auth.password.strict'] = true;

    /**
     * These files are execute on site install/upgrade/migrate if they exist
     */
    $config['db.migrate.static'] = [
        '/vendor/ttek/tk-base/config/sql/events.sql',
        '/vendor/ttek/tk-base/config/sql/triggers.sql',
        '/vendor/ttek/tk-base/config/sql/procedures.sql',
        '/vendor/ttek/tk-base/config/sql/views.sql',
        '/src/config/sql/events.sql',
        '/src/config/sql/triggers.sql',
        '/src/config/sql/procedures.sql',
        '/src/config/sql/views.sql',
    ];

    /**
     * Script to execute in dev mode after install/upgrade/migrate
     */
    $config['dev.setup.script'] = $config->get('path.config') . '/dev.php';

    /**
     * // customise request log info
     * $config['site.log.params'] = 0
     * // | StartupHandler::SITE_NAME
     * // | StartupHandler::REQUEST_URI
     * // | StartupHandler::CLIENT_IP
     * // | StartupHandler::CLIENT_AGENT
     * // | StartupHandler::SESSION_ID
     * // | StartupHandler::PHP_VER
     * // | StartupHandler::CONTROLLER
     * // | StartupHandler::METRICS
     */
    $config['site.log.params'] = 0;

    /**
     * DB mirror command secret API key and URI
     * Ensure the Secret Key is on both the dev and prod sites
     * The url is only required for the client dev site accessing the prod site
     */
    //$config['db.mirror.secret'] = '';
    //$config['db.mirror.url'] = '';

    /*
     * Send copies of all system emails to these recipients (not error emails)
     */
    //$config['mail.bcc'] = ['admin@example.org'];

};