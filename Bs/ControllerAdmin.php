<?php

namespace Bs;

abstract class ControllerAdmin extends ControllerDomInterface
{
    public function getPageTemplate(): string
    {
        if (empty($this->pageTemplate)) {
            $this->setPageTemplate($this->getConfig()->get('path.template.admin'));
        }
        return parent::getPageTemplate();
    }
}