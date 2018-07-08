<?php
namespace Bs\Console;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2017 Michael Mifsud
 */
class Iface extends \Tk\Console\Console
{

    /**
     * @return \Bs\Config
     */
    public function getConfig()
    {
        return \Bs\Config::getInstance();
    }

}
