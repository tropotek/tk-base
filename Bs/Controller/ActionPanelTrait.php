<?php
namespace Bs\Controller;

/**
 * @author Tropotek <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2017 Tropotek
 */
trait ActionPanelTrait
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
            $this->actionPanel = \Bs\Config::getInstance()->getAdminActionPanel();
        }
        return $this->actionPanel;
    }

}