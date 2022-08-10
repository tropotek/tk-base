<?php
namespace Bs;

use Tk\ConfigTrait;

/**
 * @author Michael Mifsud <http://www.tropotek.com/>
 * @link http://www.tropotek.com/
 * @license Copyright 2016 Michael Mifsud
 */
class FrontController extends \Symfony\Component\HttpKernel\HttpKernel
{
    use ConfigTrait;

}