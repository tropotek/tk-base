<?php

namespace Bs;

use Symfony\Component\HttpFoundation\Request;
use Tk\Config;
use Tk\Exception;

/**
 * This controller os used to execute a php route
 */
class PhpController
{

    public function doDefault(Request $request): string
    {
        $path = Config::makePath($request->attributes->get('path'));
        if (!is_file($path)) {
            throw new Exception("File not found {$path}");
        }

        //extract($request->attributes->all(), EXTR_SKIP);
        ob_start();
        include $path;
        return ob_get_clean();
    }
}