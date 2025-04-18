<?php

namespace Bs\Controller\Util;

use Bs\Registry;
use Symfony\Component\HttpFoundation\JsonResponse;
use Tk\Config;
use Tk\FileUtil;

class Ping
{

    public function doDefault(): JsonResponse
    {
        $data = [
            'hostname' => str_replace('www.', '', Config::instance()->get('hostname', 'localhost')),
            'siteName' => Registry::instance()->get('site.name', 'unknown'),
            'timestamp' => time(),
            'timezone' => date_default_timezone_get(),
            'bytes' => FileUtil::diskSpace(Config::makePath()),
        ];

        return new JsonResponse($data);
    }

}