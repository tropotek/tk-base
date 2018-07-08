<?php
namespace Bs\Controller\Admin;

use Tk\Request;

/**
 *
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Index extends \Bs\Controller\AdminIface
{
    /**
     * @throws \Exception
     */
    public function __construct()
    {
        $this->setPageTitle('Dashboard');
        $this->getCrumbs()->reset();
    }


    /**
     * @param Request $request
     */
    public function doDefault(Request $request)
    {
        $this->getActionPanel()->setEnabled(false);


    }

    /**
     * DomTemplate magic method
     *
     * @return \Dom\Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<HTML
<div>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }
    
}