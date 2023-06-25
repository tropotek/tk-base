<?php
use Tk\Config;

return function (Config $config)
{

    $config->set('path.template.public',      '/html/public.html');
    $config->set('path.template.admin',       '/html/public.html');
    $config->set('path.template.user',        '/html/public.html');
    $config->set('path.template.maintenance', '/html/public.html');
    $config->set('path.template.login',       '/html/login.html');

    $config->set('sql.migrate.list', [
        'App Sql' => $config->getBasePath() . '/src/config',
        'Bs Sql'  => $config->getBasePath() . $config->get('path.vendor.org') . '/tk-base/config/sql',
    ]);
};