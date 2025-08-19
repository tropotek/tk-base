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
    $routes->add('util-tk-ping', '/tkping')
        ->controller([\Bs\Controller\Util\Ping::class, 'doDefault']);
    $routes->add('util-db-search', '/util/dbSearch')
        ->controller([\Bs\Controller\Util\DbSearch::class, 'doDefault']);
    $routes->add('util-db-size', '/util/dbSize')
        ->controller([\Bs\Controller\Util\DbSize::class, 'doDefault']);

    // Site Mirror tool
    if (\Tk\Config::getValue('db.mirror.secret', false)) {
        $routes->add('system-mirror', '/util/mirror')
            ->controller([\Bs\Controller\Util\Mirror::class, 'doDefault'])
            ->schemes(['https']);
    }

    // Components
    $routes->add('com-about-dialog', '/component/aboutDialog')
        ->controller([\Bs\Component\AboutDialog::class, 'doDefault']);
    $routes->add('com-logout-dialog', '/component/logoutDialog')
        ->controller([\Bs\Component\LogoutDialog::class, 'doDefault']);
    $routes->add('com-alert-renderer', '/component/alertRenderer')
        ->controller([\Bs\Component\AlertRenderer::class, 'doDefault']);

};
