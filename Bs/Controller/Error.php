<?php
namespace Bs\Controller;

use Tk\Config;

class Error
{

    public function doDefault(\Throwable $e): string
    {
        return $this->getExceptionHtml($e, Config::isDev());
    }

    public function getExceptionHtml(\Throwable $e, bool $withTrace = false): string
    {
        $config = Config::instance();
        $class = get_class($e);
        $msg = $e->getMessage();
        $str = '';
        $extra = '';
        $logHtml = '';

        if ($withTrace) {
            $str = trim($e->__toString());
            $str = highlight_string("<?php \n" . $str, true);
            $str = str_replace(["&lt;?php", 'color: #FF8000'], ['', 'color: #666'], $str);
            $extra = sprintf("<br> in <em>%s:%s</em><br>",  $e->getFile(), $e->getLine());
        }

        $html = <<<HTML
<html lang="en">
    <head>
      <title>$class</title>
    <style>
        code,pre {
            line-height: 1.5em;
            padding: 0;
            margin: 0;
            word-wrap: normal;
        }
    </style>
    </head>
    <body style="padding: 10px;">
    <h1>$class</h1>
    <p><strong>$msg $extra</strong></p>
    <code>$str</code>
    $logHtml
    </body>
</html>
HTML;

        return strval(str_replace(Config::getBasePath(), '', $html));
    }
};