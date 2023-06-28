<?php
use Tk\Config;

return function (Config $config)
{

    /**
     * Set the default template paths
     */
    $config->set('path.template.public',      '/html/public.html');
    $config->set('path.template.admin',       '/html/public.html');
    $config->set('path.template.user',        '/html/public.html');
    $config->set('path.template.maintenance', '/html/public.html');
    $config->set('path.template.login',       '/html/login.html');

    /**
     * When set, the users can update their password from their profile page
     */
    $config['user.profile.password']    = true;

};