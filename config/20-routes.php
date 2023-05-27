<?php
use Symfony\Component\Routing\Loader\Configurator\CollectionConfigurator;

return function (CollectionConfigurator $routes) {

    $routes->add('file-manager', '/fileManager')
        ->controller([\Bs\Controller\File\Manager::class, 'doDefault']);
    $routes->add('tail-log', '/tailLog')
        ->controller([\Bs\Controller\Admin\Dev\TailLog::class, 'doDefault']);
    $routes->add('list-events', '/listEvents')
        ->controller([\Bs\Controller\Admin\Dev\ListEvents::class, 'doDefault']);

};