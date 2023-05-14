<?php
use Symfony\Component\Routing\Loader\Configurator\CollectionConfigurator;

return function (CollectionConfigurator $routes) {

    // Install page, this should be disabled if the DB is installed
    $routes->add('install', '/install')
        ->controller([\Bs\Controller\Install::class, 'doDefault']);

    $routes->add('maintenance', '/maintenance')
        ->controller([\Bs\Controller\Maintenance::class, 'doDefault']);

};