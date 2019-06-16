<?php
namespace Bs\Ui;

/**
 * Helper class to add required javascripts to templates
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2016 Michael Mifsud
 */
class Js
{


    /**
     * @param \Dom\Template $template
     * @param array $params
     */
    public static function includeGoogleMaps($template, $params = array())
    {
        $gmap = \Tk\Uri::create('//maps.googleapis.com/maps/api/js');
        $config = \Bs\Config::getInstance();
        if ($config->get('google.map.apikey')) {
            $gmap->set('key', $config->get('google.map.apikey'));
        } else if ($config->getGoogleMapKey()) {
            $gmap->set('key', $config->getGoogleMapKey());
        }
        foreach ($params as $k => $v) {
            $gmap->set($k, $v);
        }
        $template->appendJsUrl($gmap, array('data-jsl-priority' => -1000));
    }
    
    
    
    
    
    
}