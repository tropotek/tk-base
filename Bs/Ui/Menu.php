<?php
namespace Bs\Ui;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2018 Michael Mifsud
 */
class Menu extends \Tk\Ui\Menu\Menu
{


    /**
     * @param string $name
     * @param string|\Tk\Uri $url
     * @param string $icon
     */
    public function __construct($name = '', $url = null, $icon = null)
    {
        parent::__construct($name, $url, $icon);
        $this->addCss('tk-ui-menu');
    }



}

