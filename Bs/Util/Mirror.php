<?php
namespace Bs\Util;

use Bs\Uri;
use Tk\ConfigTrait;
use Tk\Log;

/**
 * @author Tropotek <http://www.tropotek.com/>
 * @link http://www.tropotek.com/
 * @license Copyright 2017 Tropotek
 */
class Mirror
{
    use ConfigTrait;

    public function doDbBackup(\Tk\Request $request)
    {
//        if (!$this->getConfig()->isDebug()) {
//            throw new \Tk\Exception('Only available for live sites.');
//        }
        if (strtolower($request->getScheme()) != Uri::SCHEME_HTTP_SSL) {
            throw new \Tk\Exception('Only available over SSL connections.');
        }
        if (!$this->getConfig()->get('db.skey') || $this->getConfig()->get('db.skey') != $request->request->get('db_skey')) {
            throw new \Tk\Exception('Invalid security key.');
        }

        $dbBackup = \Tk\Util\SqlBackup::create($this->getConfig()->getDb());
        $exclude = array(\Tk\Session\Adapter\Database::$DB_TABLE);

        $path = $this->getConfig()->getTempPath() . '/db_mirror.sql';
        $dbBackup->save($path, ['exclude' => $exclude]);

        @unlink($path . '.gz');
        $command = sprintf('gzip ' . $path);
        exec($command, $out, $ret);
        if ($ret != 0) {
            throw new \Tk\Db\Exception(implode("\n", $out));
        }
        $path .= '.gz';

        $public_name = basename($path);
        $filesize = filesize($path);
        header("Content-Disposition: attachment; filename=$public_name;");
        header("Content-Type: application/octet-stream");
        header('Content-Length: '.$filesize);
        $this->_fileOutput($path);
        if (is_file($path)) unlink($path);

        exit;
    }

    protected function _fileOutput($filename)
    {
        $filesize = filesize($filename);
        $chunksize = 4096;
        if($filesize > $chunksize) {
            $srcStream = fopen($filename, 'rb');
            $dstStream = fopen('php://output', 'wb');
            $offset = 0;
            while(!feof($srcStream)) {
                $offset += stream_copy_to_stream($srcStream, $dstStream, $chunksize, $offset);
            }
            fclose($dstStream);
            fclose($srcStream);
        } else {
            // stream_copy_to_stream behaves() strange when filesize > chunksize.
            // Seems to never hit the EOF.
            // On the other handside file_get_contents() is not scalable.
            // Therefore, we only use file_get_contents() on small files.
            echo file_get_contents($filename);
        }
    }

    public function doDataBackup(\Tk\Request $request)
    {
//        if (!$this->getConfig()->isDebug()) {
//            throw new \Tk\Exception('Only available for live sites.');
//        }
        if (strtolower($request->getScheme()) != Uri::SCHEME_HTTP_SSL) {
            throw new \Tk\Exception('Only available over SSL connections.');
        }
        if (!$this->getConfig()->get('db.skey') || $this->getConfig()->get('db.skey') != $request->request->get('db_skey')) {
            throw new \Tk\Exception('Invalid security key.');
        }

        $srcFile = $this->getConfig()->getSitePath() . '/src-'.\Tk\Date::create()->format(\Tk\Date::FORMAT_ISO_DATE).'-data.tgz';
        if (is_file($srcFile)) unlink($srcFile);
        $cmd = sprintf('cd %s && tar zcf %s %s',
            $this->getConfig()->getSitePath(),
            escapeshellarg(basename($srcFile)),
            basename($this->getConfig()->getDataPath())
        );
        //Log::info($cmd);
        system($cmd);

        $public_name = basename($srcFile);
        $filesize = filesize($srcFile);
        header("Content-Disposition: attachment; filename=$public_name;");
        header("Content-Type: application/octet-stream");
        header('Content-Length: '.$filesize);
        $this->_fileOutput($srcFile);
        if (is_file($srcFile)) unlink($srcFile);

        exit;
    }

}
