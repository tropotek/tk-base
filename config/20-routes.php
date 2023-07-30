<?php
use Symfony\Component\Routing\Loader\Configurator\CollectionConfigurator;

return function (CollectionConfigurator $routes) {

    $routes->add('file-manager', '/fileManager')
        ->defaults(['template' => \Bs\Page::TEMPLATE_ADMIN])
        ->controller([\Bs\Controller\File\Manager::class, 'doDefault']);

    $routes->add(\Bs\Page::TEMPLATE_LOGIN, '/login')
        ->defaults(['template' => \Bs\Page::TEMPLATE_LOGIN])
        ->controller([\Bs\Controller\User\Login::class, 'doLogin']);
    $routes->add('logout', '/logout')
        ->defaults(['template' => \Bs\Page::TEMPLATE_LOGIN])
        ->controller([\Bs\Controller\User\Login::class, 'doLogout']);
    $routes->add('recover', '/recover')
        ->defaults(['template' => \Bs\Page::TEMPLATE_LOGIN])
        ->controller([\Bs\Controller\User\Recover::class, 'doDefault']);
    $routes->add('recover-pass', '/recoverUpdate')
        ->defaults(['template' => \Bs\Page::TEMPLATE_LOGIN])
        ->controller([\Bs\Controller\User\Recover::class, 'doRecover']);
    $routes->add('register', '/register')
        ->defaults(['template' => \Bs\Page::TEMPLATE_LOGIN])
        ->controller([\Bs\Controller\User\Register::class, 'doDefault']);
    $routes->add('register-activate', '/registerActivate')
        ->defaults(['template' => \Bs\Page::TEMPLATE_LOGIN])
        ->controller([\Bs\Controller\User\Register::class, 'doActivate']);

    $routes->add('user-profile', '/profile')
        ->defaults(['template' => \Bs\Page::TEMPLATE_USER])
        ->controller([\Bs\Controller\User\Profile::class, 'doDefault']);
    $routes->add('user-manager', '/user/manager')
        ->defaults(['template' => \Bs\Page::TEMPLATE_ADMIN])
        ->controller([\Bs\Controller\User\Manager::class, 'doDefault']);
    $routes->add('user-type-manager', '/user/{type}Manager')
        ->defaults(['template' => \Bs\Page::TEMPLATE_ADMIN])
        ->controller([\Bs\Controller\User\Manager::class, 'doByType'])
        ->defaults(['type' => \Bs\Db\User::TYPE_MEMBER]);
    $routes->add('user-type-edit', '/user/{type}Edit')
        ->defaults(['template' => \Bs\Page::TEMPLATE_ADMIN])
        ->controller([\Bs\Controller\User\Edit::class, 'doDefault'])
        ->defaults(['type' => \Bs\Db\User::TYPE_MEMBER]);

    $routes->add('tail-log', '/tailLog')
        ->defaults(['template' => \Bs\Page::TEMPLATE_ADMIN])
        ->controller([\Bs\Controller\Admin\Dev\TailLog::class, 'doDefault']);
    $routes->add('list-events', '/listEvents')
        ->defaults(['template' => \Bs\Page::TEMPLATE_ADMIN])
        ->controller([\Bs\Controller\Admin\Dev\ListEvents::class, 'doDefault']);

};
