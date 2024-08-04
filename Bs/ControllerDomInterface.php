<?php
namespace Bs;

use Dom\Renderer\RendererInterface;
use Dom\Renderer\Traits\RendererTrait;

abstract class ControllerDomInterface extends ControllerInterface implements RendererInterface
{
    use RendererTrait;

}