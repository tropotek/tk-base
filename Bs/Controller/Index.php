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

    public function __construct()
    {
        $this->setPageTitle('Home');
    }

    /**
     * @param Request $request
     */
    public function doDefault(Request $request)
    {
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

    /**
     * @return \Dom\Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<HTML
<div>
  <h1>Home</h1>
  <p>TODO: Add some content here!</p>
</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }


}