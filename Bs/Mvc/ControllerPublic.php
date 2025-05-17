<?php

namespace Bs\Mvc;

use Tk\Config;

abstract class ControllerPublic extends ControllerDomInterface
{
    public function getPageTemplate(): string
    {
        if (empty($this->pageTemplate)) {
            $this->setPageTemplate(Config::getValue('path.template.public'));
        }
        return parent::getPageTemplate();
    }
}