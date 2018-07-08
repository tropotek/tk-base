<?php
namespace Bs\Controller;

use Tk\Request;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Index extends Iface
{


    /**
     * @param Request $request
     */
    public function doDefault(Request $request)
    {
        $this->setPageTitle('Home');
        // TODO:

    }


    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $template = parent::show();
        
        return $template;
    }


}