<?php
use Symfony\Component\Routing\Loader\Configurator\CollectionConfigurator;

return function (CollectionConfigurator $routes) {

    $routes->add('file-manager', '/fileManager')
        ->controller([\Bs\Controller\File\Manager::class, 'doDefault']);


    $routes->add('login', '/login')
        ->controller([\Bs\Controller\User\Login::class, 'doLogin']);
    $routes->add('logout', '/logout')
        ->controller([\Bs\Controller\User\Login::class, 'doLogout']);
    $routes->add('recover', '/recover')
        ->controller([\Bs\Controller\User\Recover::class, 'doDefault']);
    $routes->add('recover-pass', '/recoverUpdate')
        ->controller([\Bs\Controller\User\Recover::class, 'doRecover']);
    $routes->add('register', '/register')
        ->controller([\Bs\Controller\User\Register::class, 'doDefault']);
    $routes->add('register-activate', '/registerActivate')
        ->controller([\Bs\Controller\User\Register::class, 'doActivate']);

    $routes->add('user-profile', '/profile')
        ->controller([\Bs\Controller\User\Profile::class, 'doDefault']);
    $routes->add('user-manager', '/user/manager')
        ->controller([\Bs\Controller\User\Manager::class, 'doDefault']);
    $routes->add('user-type-manager', '/user/{type}Manager')
        ->controller([\Bs\Controller\User\Manager::class, 'doByType'])
        ->defaults(['type' => \Bs\Db\User::TYPE_MEMBER]);
    $routes->add('user-type-edit', '/user/{type}Edit')
        ->controller([\Bs\Controller\User\Edit::class, 'doDefault'])
        ->defaults(['type' => \Bs\Db\User::TYPE_MEMBER]);


    $routes->add('tail-log', '/tailLog')
        ->controller([\Bs\Controller\Admin\Dev\TailLog::class, 'doDefault']);
    $routes->add('list-events', '/listEvents')
        ->controller([\Bs\Controller\Admin\Dev\ListEvents::class, 'doDefault']);

};
