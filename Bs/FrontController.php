<?php
namespace Bs;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2016 Michael Mifsud
 */
class FrontController extends \Symfony\Component\HttpKernel\HttpKernel
{

    /**
     * @return Config
     */
    public function getConfig()
    {
        return Config::getInstance();
    }

}