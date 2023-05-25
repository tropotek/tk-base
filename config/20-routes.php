<?php
use Symfony\Component\Routing\Loader\Configurator\CollectionConfigurator;

return function (CollectionConfigurator $routes) {

    $routes->add('file-manager', '/fileManager')
        ->controller([\Bs\Controller\File\Manager::class, 'doDefault']);

};