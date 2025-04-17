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
            'siteName' => Registry::instance()->get('site.name', 'unknown'),
            'timestamp' => time(),
            'timezone' => date_default_timezone_get(),
            'status' => 'OK',
            'bytes' => FileUtil::diskSpace(Config::makePath()),
        ];
vd($data);
        return new JsonResponse($data);
    }

}