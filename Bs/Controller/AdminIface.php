<?php
namespace Bs\Controller;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class AdminIface extends Iface
{

    /**
     * @var \Tk\Ui\Admin\ActionPanel
     */
    protected $actionPanel = null;


    /**
     * @return \Tk\Ui\Admin\ActionPanel
     */
    public function getActionPanel()
    {
        if (!$this->actionPanel) {
            $this->actionPanel = $this->getConfig()->getAdminActionPanel();
        }
        return $this->actionPanel;
    }


}