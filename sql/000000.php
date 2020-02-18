<?php

// This is executed here to let the \Tk\Db\Data object control the creation of the Data table.
$config = \App\Config::getInstance();
try {
    $data = \Tk\Db\Data::create();
    if (!$data->get('site.title')) {
        $data->set('site.title', 'Tk II Project');
        $data->set('site.short.title', 'Tk2Base');
    }
    if (!$data->get('site.email'))
        $data->set('site.email', 'admin@example.com');
    //$data->set('site.client.registration', 'site.client.registration');
    //$data->set('site.client.activation', 'site.client.activation');

    if (!$data->get('site.meta.keywords'))
        $data->set('site.meta.keywords', '');
    if (!$data->get('site.meta.description'))
        $data->set('site.meta.description', '');
    if (!$data->get('site.global.js'))
        $data->set('site.global.js', '');
    if (!$data->get('site.global.css'))
        $data->set('site.global.css', '');

    $data->save();
} catch (\Tk\Db\Exception $e) {
}




