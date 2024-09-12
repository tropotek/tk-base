<?php

namespace Bs;

use Tk\Config;

abstract class ControllerAdmin extends ControllerDomInterface
{
    public function getPageTemplate(): string
    {
        if (empty($this->pageTemplate)) {
            $this->setPageTemplate(Config::instance()->get('path.template.admin'));
        }
        return parent::getPageTemplate();
    }
}