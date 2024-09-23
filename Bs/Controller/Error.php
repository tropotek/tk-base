<?php
namespace Bs\Controller;

use Tk\Config;

class Error
{

    public function doDefault(\Throwable $e): string
    {
        return $this->getExceptionHtml($e, Config::isDebug());
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
            $toString = trim($e->__toString());
            $str = str_replace(["&lt;?php&nbsp;<br />", 'color: #FF8000'], ['', 'color: #666'],
                highlight_string("<?php \n" . $toString, true));
            $extra = sprintf('<br/>in <em>%s:%s</em>',  $e->getFile(), $e->getLine());
        }

        $html = <<<HTML
<html lang="en">
    <head>
      <title>$class</title>
    <style>
    code, pre {
      line-height: 1.4em;
      padding: 0;margin: 0;
      overflow: auto;
    }
    </style>
    </head>
    <body style="padding: 10px;">
    <h1>$class</h1>
    <p><strong>$msg $extra</strong></p>
    <pre style="">$str</pre>
    $logHtml
    </body>
</html>
HTML;

        return strval(str_replace(Config::getBasePath(), '', $html));
    }
};