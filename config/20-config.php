<?php
use Tk\Config;

return function (Config $config)
{

    $config->set('path.template.public', '/html/public.html');
    $config->set('path.template.admin',  '/html/public.html');
    $config->set('path.template.user',   '/html/public.html');
    $config->set('path.template.login',  '/html/login.html');

};