<?php

namespace Bs\Mvc;

use Symfony\Component\HttpFoundation\Request;
use Tk\Config;
use Tk\Exception;
use Tk\Path;

/**
 * This controller os used to execute a php route
 */
class PhpController
{

    public function doDefault(Request $request): string
    {
        $path = Path::create($request->attributes->get('path'));
        if (!is_file($path)) {
            throw new Exception("File not found {$path}");
        }

        //extract($request->attributes->all(), EXTR_SKIP);
        ob_start();
        include $path;
        return strval(ob_get_clean());
    }
}