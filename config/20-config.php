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
     * mail template path
     */
    $config['system.mail.template'] = '/html/templates/mail.default.html';

    /**
     * These files are execute on site install/upgrade/migrate if they exist
     */
    $config['db.migrate.static'] = [
        '/vendor/ttek/tk-base/config/sql/procedures.sql',
        '/vendor/ttek/tk-base/config/sql/views.sql',
        '/vendor/ttek/tk-base/config/sql/triggers.sql',
        '/vendor/ttek/tk-base/config/sql/events.sql',
        '/src/config/sql/procedures.sql',
        '/src/config/sql/views.sql',
        '/src/config/sql/events.sql',
        '/src/config/sql/triggers.sql',
    ];

    /**
     * Script to execute after install/upgrade/migrate
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
     * The default log level
     */
    $config['log.logLevel'] = \Psr\Log\LogLevel::ERROR;

    /**
     * Set the site timezone for PHP and MySQL
     */
    $config['php.date.timezone'] = 'Australia/Melbourne';

    /**
     * Enable DB sessions
     */
    $config['session.db_enable'] = true;

    /**
     * The site developer information
     * Use this for online support contact forms and copyright
     */
    $config['developer.name']  = 'Tropotek';
    $config['developer.web']   = 'https://tropotek.com.au/';
    $config['developer.email'] = 'info@tropotek.com.au';

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
     * DB mirror command secret API key and URI
     * Ensure the Secret Key is on both the dev and prod sites
     * The url is only required for the client dev site accessing the prod site
     */
    //$config['db.mirror.secret'] = '';
    //$config['db.mirror.url'] = '';

    /*
     * Send copies of all system emails to these recipients (not error emails)
     */
    //$config['mail.bcc'] = ['user@example.org'];

    /**
     * System hostname, used for command line scripts (cron)
     * If not set the system will auto-detect the hostname from an http request and cache it
     * Remember to purge all caches if the hostname ever changes
     */
    //$config['hostname'] = '';

};