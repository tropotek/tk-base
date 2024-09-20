<?php
use Symfony\Component\Routing\Loader\Configurator\CollectionConfigurator;

return function (CollectionConfigurator $routes) {

    // User Public
    $routes->add('login', '/login')
        ->controller([\Bs\Controller\User\Login::class, 'doLogin']);
    $routes->add('logout', '/logout')
        ->controller([\Bs\Controller\User\Login::class, 'doLogout']);
    $routes->add('recover', '/recover')
        ->controller([\Bs\Controller\User\Recover::class, 'doDefault']);
    $routes->add('recover-pass', '/recoverUpdate')
        ->controller([\Bs\Controller\User\Recover::class, 'doRecover']);
    $routes->add('register-activate', '/registerActivate')
        ->controller([\Bs\Controller\User\Register::class, 'doActivate']);
    if (\Tk\Config::instance()->get('user.registration.enable', false)) {
        $routes->add('register', '/register')
            ->controller([\Bs\Controller\User\Register::class, 'doDefault']);
    }

    // User Admin
    $routes->add('user-profile', '/profile')
        ->controller([\Bs\Controller\User\Profile::class, 'doDefault']);
    $routes->add('user-type-manager', '/user/{type}Manager')
        ->controller([\Bs\Controller\User\Manager::class, 'doByType'])
        ->defaults(['type' => \Bs\Db\User::TYPE_MEMBER]);
    $routes->add('user-type-edit', '/user/{type}Edit')
        ->controller([\Bs\Controller\User\Edit::class, 'doDefault'])
        ->defaults(['type' => \Bs\Db\User::TYPE_MEMBER]);

    // Filesystem
    $routes->add('file-manager', '/fileManager')
        ->controller([\Bs\Controller\File\Manager::class, 'doDefault']);

    // Dev/info Pages
    $routes->add('sessions', '/sessions')
        ->controller([\Bs\Controller\Admin\Dev\Sessions::class, 'doDefault']);
    $routes->add('phpinfo', '/info')
        ->controller([\Bs\Controller\Admin\Dev\Info::class, 'doDefault']);
    $routes->add('tail-log', '/tailLog')
        ->controller([\Bs\Controller\Admin\Dev\TailLog::class, 'doDefault']);

    // Utils
    if (\Tk\Config::instance()->get('db.mirror.secret', false)) {
        $routes->add('system-mirror', '/util/mirror')
            ->controller([\Bs\Controller\Util\Mirror::class, 'doDefault']);
    }

    // Example php route
    // $routes->add('widget-test', '/widgetTest')
    //     ->defaults(['path' => '/page/widgetManager.php'])
    //     ->controller([\Bs\PhpController::class, 'doDefault']);
};
