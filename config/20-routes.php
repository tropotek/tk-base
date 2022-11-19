<?php
use Symfony\Component\Routing\Loader\Configurator\CollectionConfigurator;

return function (CollectionConfigurator $routes) {

    // Install page, this should be dissabled if the DB is installed
    $routes->add('install', '/install')
        ->controller([\Bs\Controller\Install::class, 'doDefault']);

    $routes->add('maintenance', '/maintenance')
        ->controller([\Bs\Controller\Maintenance::class, 'doDefault']);

    // Auth pages Login, Logout, Register, Recover
    $routes->add('login', '/login')
        ->controller([\Bs\Controller\User\Login::class, 'doLogin']);
    $routes->add('logout', '/logout')
        ->controller([\Bs\Controller\User\Login::class, 'doLogout']);

};