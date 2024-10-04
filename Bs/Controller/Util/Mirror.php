<?php
namespace Bs\Controller\Util;

use Bs\Auth;
use JetBrains\PhpStorm\NoReturn;
use Tk\Config;
use Tk\Uri;
use Tk\Db;

/**
 * @todo Update the mirror command to encrypt the sql file before saving and after extracting.
 *      possibly look into adding a pw to the compressed file, maybe use zip if gz dos not have this.
 */
class Mirror
{

    public function doDefault(): string
    {
        if (strtolower($_SERVER['REQUEST_SCHEME']) != Uri::SCHEME_HTTP_SSL) {
            throw new \Tk\Exception('invalid SSL connection');
        }
        if (!Config::instance()->get('db.mirror.secret', false)) {
            throw new \Tk\Exception('access disabled');
        }

        $action   = trim($_POST['action'] ?? '');
        $username = trim($_POST['un'] ?? '');
        $secret   = trim($_POST['secret'] ?? '');
        if (Config::instance()->get('db.mirror.secret', null) !== $secret) {
            throw new \Tk\Exception('invalid access key');
        }

        $user = Auth::findByUsername($username);
        if (is_null($user) || !$user->isAdmin()) {
            throw new \Tk\Exception('Invalid access permission');
        }

        if ($action == 'db') {
            $this->doDbBackup();
        } elseif ($action == 'file') {
            $this->doDataBackup();
        }

        return 'Invalid access request.';
    }

    /**
     * @todo exclude cache, tmp folders
     */
    #[NoReturn] public function doDataBackup()
    {
        $srcFile = Config::makePath('/src-'.\Tk\Date::create()->format(\Tk\Date::FORMAT_ISO_DATE).'-data.tgz');
        if (is_file($srcFile)) unlink($srcFile);
        $cmd = sprintf('cd %s && tar zcf %s %s',
            Config::getBasePath(),
            escapeshellarg(basename($srcFile)),
            basename(Config::makePath(Config::getDataPath()))
        );
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

    public function doDbBackup(): void
    {
        $options = Db::parseDsn(Config::instance()->get('db.mysql'));
        // must exclude _migrate table for migrate cmd to work in mirror cmd
        $options['exclude'] = ['_session', '_migrate'];

        $path = Config::makePath(Config::getTempPath() . '/' . \Tk\Date::create()->format(\Tk\Date::FORMAT_ISO_DATE) . '-tmpl.sql');
        Db\DbBackup::save($path, $options);

        if (is_file($path . '.gz'))
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

        exit;
    }

    protected function _fileOutput($filename): void
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
            // On the other hand file_get_contents() is not scalable.
            // Therefore, we only use file_get_contents() on small files.
            echo file_get_contents($filename);
        }
    }

}
