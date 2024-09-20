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
     * Set the homepage url for users
     */
    $config->set('auth.home.url', [
        \Bs\Db\User::class => '/dashboard',
    ]);

    /**
     * Can users update their password from their profile page
     * (default: false)
     */
    $config->set('auth.profile.password', false);

    /**
     * Can users register an account
     * (default: false)
     */
    $config->set('auth.registration.enable', false);

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
    $config->set('auth.password.strict', true);


};