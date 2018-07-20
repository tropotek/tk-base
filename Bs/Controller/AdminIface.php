<?php
namespace Bs\Controller;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class AdminIface extends Iface
{
    use ActionPanelTrait;

    /**
     * @return string
     */
    public function getPageTemplatePath()
    {
        return $this->getConfig()->getSitePath() . $this->getConfig()->get('template.admin');
    }

}