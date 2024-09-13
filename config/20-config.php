<?php
use Tk\Config;

/*
 * @see https://symfony.com/doc/current/routing.html
 *
 * Selecting a template:
 *   You can select the page's template by adding `->defaults(['template' => '{public|admin|user|login|maintenance|error}'])`.
 *
 *   Other options may be available if you have created new template paths in the `20-config.php` file.
 *   Create a new path with `$config->set('path.template.custom', '/html/newTemplate/index.html');`
 *   then add `->defaults(['template' => 'custom'])` to the route. (case-sensitive)
 *
 */
return function (Config $config) {

    /**
     * Set the default template paths
     */
    $config->set('path.template.public',      '/html/public.html');
    $config->set('path.template.admin',       '/html/public.html');
    $config->set('path.template.user',        '/html/public.html');
    $config->set('path.template.login',       '/html/login.html');
    $config->set('path.template.maintenance', '/html/login.html');
    $config->set('path.template.error',       '/html/login.html');

    /**
     * Script to execute in dev mode after update/migrate
     */
    $config->set('debug.script', $config->get('path.config') . '/dev.php');

    /**
     * Set the homepage url for user types
     */
    $config->set('user.homepage', [
        \Bs\Db\User::TYPE_STAFF => '/dashboard',
        \Bs\Db\User::TYPE_MEMBER => '/',
    ]);

    /**
     * Can users update their password from their profile page
     * (default: false)
     */
    $config->set('user.profile.password', false);

    /**
     * Can users register an account
     * (default: false)
     */
    $config->set('user.registration.enable', false);

    /**
     * Default type of new user registered account
     * (default: 'member')
     */
    $config->set('user.default.type', \Bs\Db\User::TYPE_MEMBER);

    /**
     * Use to store a copy of the last log for detailed Exception logs
     */
    //$config->set('log.system.request', $config->get('path.cache') . '/requestLog.txt');

    // These files are execute on update/migrate if they exist
    $config->set('db.migrate.static', [
        '/vendor/ttek/tk-base/config/sql/views.sql',
        '/vendor/ttek/tk-base/config/sql/procedures.sql',
        '/vendor/ttek/tk-base/config/sql/events.sql',
        '/vendor/ttek/tk-base/config/sql/triggers.sql',
        '/src/config/sql/views.sql',
        '/src/config/sql/procedures.sql',
        '/src/config/sql/events.sql',
        '/src/config/sql/triggers.sql'
    ]);
};