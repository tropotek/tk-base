<?php
namespace Bs;

use Tk\Event\Dispatcher;
use Tk\Controller\Resolver;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2016 Michael Mifsud
 */
class FrontController extends \Tk\Kernel\HttpKernel
{

    /**
     * @param Dispatcher $dispatcher
     * @param Resolver $resolver
     * @throws \Tk\Exception
     */
    public function __construct(Dispatcher $dispatcher, Resolver $resolver)
    {
        parent::__construct($dispatcher, $resolver);
        $this->getConfig()->setupDispatcher($dispatcher);
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return Config::getInstance();
    }

}