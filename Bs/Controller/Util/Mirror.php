<?php
namespace Bs\Controller\Util;

use Bs\Auth;
use Tk\Config;
use Tk\Encrypt;
use Tk\Log;
use Tk\Db;
use Tk\Path;

/**
 * @todo Update the mirror command to encrypt the sql file before saving and after extracting.
 *      possibly look into adding a pw to the compressed file, maybe use zip if gz dos not have this.
 */
class Mirror
{

    public function doDefault(): string
    {
        set_time_limit(0);

        if (strtolower($_SERVER['REQUEST_SCHEME']) != 'https') {
            throw new \Tk\Exception('invalid SSL connection');
        }
        $secret = Config::getValue('db.mirror.secret', '');
        if (empty($secret)) {
            throw new \Tk\Exception('access disabled');
        }

        $enc = Encrypt::create($secret);
        $action   = trim($_POST['a'] ?? '');
        $username = $enc->basicDecrypt(trim($_POST['u'] ?? ''));
        $password = $enc->basicDecrypt(trim($_POST['p'] ?? ''));
        $all = isset($_POST['all']);    // copy all, include private folder

        $user = Auth::findByUsername($username);
        if (is_null($user) || !$user->isAdmin()) {
            throw new \Tk\Exception('Invalid access permission');
        }
        if (!password_verify($password, $user->password)) {
            throw new \Tk\Exception('Invalid access permission');
        }

        $headers = getallheaders();
        $secretKey  = trim($headers['authorization-key'] ?? $headers['Authorization-Key'] ?? '');
        if ($secret !== $secretKey) {
            throw new \Tk\Exception('invalid access key');
        }

        if ($action == 'db') {
            $this->doDbBackup();
        } elseif ($action == 'file') {
            $this->doDataBackup($all);
        }

        return 'Invalid access request.';
    }

    public function doDataBackup(bool $all = false): void
    {
        set_time_limit(0);

        $srcFile = tempnam(Path::create(), '_mifl');
        if (is_file($srcFile)) unlink($srcFile);
        if ($all) {
            $cmd = sprintf('cd %s && tar -zcf %s %s',
                escapeshellarg(Config::getBasePath()),
                escapeshellarg(basename($srcFile)),
                escapeshellarg(basename(Path::createDataPath()))
            );
        } else {
            $cmd = sprintf('cd %s && tar --exclude=%s -zcf %s %s',
                escapeshellarg(Config::getBasePath()),
                escapeshellarg('private'),
                escapeshellarg(basename($srcFile)),
                escapeshellarg(basename(Path::createDataPath()))
            );
        }

        [$result, $code] = $this->execTimeout($cmd, 60*60*2);

//        $result = [];
//        $code = 0;
//        exec($cmd, $result, $code);
        if ($code != 0) {
            @unlink($srcFile);
            throw new \Tk\Exception(implode("\n", $result));
        }

        $public_name = basename($srcFile);
        $filesize = filesize($srcFile);
        header("Content-Disposition: attachment; filename=$public_name;");
        header("Content-Type: application/octet-stream");
        header('Content-Length: '.$filesize);
        $this->_fileOutput($srcFile);

        if (is_file($srcFile)) unlink($srcFile);

        exit;
    }

    protected function execTimeout(string $cmd, int $timeout = 60): array
    {
        $start = time();
        //$outfile = uniqid('/tmp/out',1);
        $result = [];
        $code = -1;
        //$pid = trim(shell_exec("$cmd >$outfile 2>&1 & echo $!"));
        $pid = trim(exec("$cmd 2>&1 & echo $!", $result, $code));
        if(empty($pid)) return false;
        while(1){
            if((time()-$start) > $timeout){
                //exec("kill -9 $pid",$null);
                exec("kill -9 $pid");
                break;
            }
            //$exists=trim(shell_exec("ps -p $pid -o pid="));
            $exists = trim(exec("ps -p $pid -o pid="));
            if(empty($exists)) break;
            sleep(1);
        }
//        $output=file_get_contents($outfile);
//        unlink($outfile);
        return [$result, $code];
    }

    public function doDbBackup(): void
    {
        set_time_limit(0);

        $options = Db::parseDsn(Config::getValue('db.mysql'));
        // must exclude _migrate table for migrate cmd to work in mirror cmd
        //$options['exclude'] = ['_session', '_migrate'];
        $options['exclude'] = ['_session'];

        $srcBak = tempnam(Path::createTempPath(), 'midb');
        Db\DbBackup::save($srcBak, $options);

        if (is_file($srcBak . '.gz')) {
            @unlink($srcBak . '.gz');
        }

        $command = sprintf('gzip ' . $srcBak);
        exec($command, $out, $ret);
        if ($ret != 0) {
            throw new \Tk\Db\Exception(implode("\n", $out));
        }
        $srcBak .= '.gz';

        $public_name = basename($srcBak);
        $filesize = filesize($srcBak);
        header("Content-Disposition: attachment; filename=$public_name;");
        header("Content-Type: application/octet-stream");
        header('Content-Length: '.$filesize);
        $this->_fileOutput($srcBak);

        if (is_file($srcBak)) unlink($srcBak);

        exit;
    }

    protected function _fileOutput(string $filename): void
    {
        $filesize = filesize($filename);
        $chunksize = 4096;
        if($filesize > $chunksize) {
            $srcStream = fopen($filename, 'rb');
            if ($srcStream === false) {
                Log::error("Cannot open file: $filename");
                return;
            }
            $dstStream = fopen('php://output', 'wb');
            if ($dstStream === false) {
                Log::error("Cannot open default output stream");
                return;
            }
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
