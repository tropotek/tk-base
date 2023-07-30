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
     * When set, the users can update their password from their profile page
     */
    $config['user.profile.password']    = true;

};