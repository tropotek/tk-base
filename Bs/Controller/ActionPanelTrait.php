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
            $this->actionPanel = \Tk\Ui\Admin\ActionPanel::create('Actions', 'fa fa-cogs');
            $this->actionPanel->append(\Tk\Ui\Link::createBtn('Back', 'javascript: window.history.back();', 'fa fa-arrow-left'))
                ->addCss('btn-default btn-once back');
        }
        return $this->actionPanel;
    }

}