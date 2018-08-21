<?php
namespace Uni\Ui;

use Tk\Ui\Link;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2018 Michael Mifsud
 */
class Menu extends \Tk\Ui\Menu\Menu
{

    /**
     * @var string
     */
    protected $templateVar = 'nav';

    /**
     * @var string
     */
    protected $roleType = 'public';




    /**
     * @param Link $link
     */
    public function __construct($link = null)
    {
        parent::__construct($link);
        $this->addCss('tk-ui-menu');
    }



    /**
     * @return string
     */
    public function getTemplateVar()
    {
        return $this->templateVar;
    }

    /**
     * @param string $templateVar
     * @return static
     */
    public function setTemplateVar($templateVar)
    {
        $this->templateVar = $templateVar;
        return $this;
    }

    /**
     * @return string
     */
    public function getRoleType()
    {
        return $this->roleType;
    }

    /**
     * @param string $roleType
     * @return static
     */
    public function setRoleType($roleType)
    {
        $this->roleType = $roleType;
        return $this;
    }


}

