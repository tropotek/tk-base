<?php
use Symfony\Component\Routing\Loader\Configurator\CollectionConfigurator;

return function (CollectionConfigurator $routes) {

    // Dev/info Pages
    $routes->add('sessions', '/sessions')
        ->controller([\Bs\Controller\Admin\Dev\Sessions::class, 'doDefault']);
    $routes->add('phpinfo', '/info')
        ->controller([\Bs\Controller\Admin\Dev\Info::class, 'doDefault']);
    $routes->add('tail-log', '/tailLog')
        ->controller([\Bs\Controller\Admin\Dev\TailLog::class, 'doDefault']);
    $routes->add('util-inline-image', '/util/inlineImage')
        ->controller([\Bs\Controller\Util\InlineImage::class, 'doDefault']);
    $routes->add('util-db-search', '/util/dbSearch')
        ->controller([\Bs\Controller\Util\DbSearch::class, 'doDefault']);

    // Site Mirror tool
    if (\Tk\Config::instance()->get('db.mirror.secret', false)) {
        $routes->add('system-mirror', '/util/mirror')
            ->controller([\Bs\Controller\Util\Mirror::class, 'doDefault'])
            ->schemes(['https']);
    }

    // Example php route
    // $routes->add('widget-test', '/widgetTest')
    //     ->defaults(['path' => '/page/widgetManager.php'])
    //     ->controller([\Bs\PhpController::class, 'doDefault']);
};
