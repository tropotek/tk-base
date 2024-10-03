<?php

namespace Bs\Mvc;

abstract class ControllerPublic extends ControllerDomInterface
{
    public function getPageTemplate(): string
    {
        if (empty($this->pageTemplate)) {
            $this->setPageTemplate($this->getConfig()->get('path.template.public'));
        }
        return parent::getPageTemplate();
    }
}