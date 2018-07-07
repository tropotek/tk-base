<?php


$config = \App\Config::getInstance();
try {
    $data = \Tk\Db\Data::create();

    $data->set('site.title', 'New Project');
    $data->set('site.email', 'admin@example.com');
    //$data->set('site.client.registration', 'site.client.registration');
    //$data->set('site.client.activation', 'site.client.activation');

    $data->set('site.meta.keywords', '');
    $data->set('site.meta.description', '');
    $data->set('site.global.js', '');
    $data->set('site.global.css', '');

    $data->save();
} catch (\Tk\Db\Exception $e) {
}




