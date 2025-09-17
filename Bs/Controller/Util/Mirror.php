<?php
namespace Bs\Controller\Util;

use Bs\Auth;
use Symfony\Component\HttpFoundation\JsonResponse;
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

    public function doDefault(): mixed
    {
        set_time_limit(0);

        if (strtolower($_SERVER['REQUEST_SCHEME']) != 'https') {
            throw new \Tk\Exception('invalid SSL connection');
        }

        $secret = Config::getValue('db.mirror.secret', '');
        if (empty($secret)) {
            throw new \Tk\Exception('access disabled');
        }

        $enc      = Encrypt::create($secret);
        $action   = trim($_POST['a'] ?? '');
        $username = $enc->basicDecrypt(trim($_POST['u'] ?? ''));
        $password = $enc->basicDecrypt(trim($_POST['p'] ?? ''));
        $all      = isset($_POST['all']);    // copy all, include private folder

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
            return $this->doDataBackup($all);
        }

        return 'Invalid access request.';
    }

    public function doDbBackup(): void
    {
        set_time_limit(0);

        $options = Db::parseDsn(Config::getValue('db.mysql'));
        // tables to exclude from backup
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

    public function doDataBackup(bool $all = false): mixed
    {
        set_time_limit(0);
        $pid = intval($_POST['pid'] ?? 0);

        if ($pid > 0) {
            if (empty($_POST['filename'] ?? '')) {
                throw new \Tk\Exception("Destination filename required");
            }
            $destFile = Path::create('/' . trim($_POST['filename'] ?? ''));
            if (!is_file($destFile)) {
                // Download has finished and should exit, this should not be reached.
                return new JsonResponse((object)[
                    'complete' => true,
                ]);
            }

            // poll for pid to be completed
            $exists = trim(exec("ps -p $pid -o pid="));
            if(empty($exists)) {
                // start file download
                $public_name = basename($destFile);
                $filesize = filesize($destFile);
                header("Content-Disposition: attachment; filename=$public_name;");
                header("Content-Type: application/octet-stream");
                header('Content-Length: '.$filesize);
                $this->_fileOutput($destFile);

                if (is_file($destFile)) unlink($destFile);
            } else {
                // Return PID and destFile AS JSON while still running
                return new JsonResponse((object)[
                    'pid' => $pid,
                    'filename' => basename($destFile),
                ]);
            }

        } else {
            // Start creating the destFile package
            if (count(glob(Path::create('/_mifl*')))) {
                throw new \Tk\Exception("There is currently a backup in progress. Please try again later.");
            }

            $destFile = tempnam(Path::create(), '_mifl');
            if (is_file($destFile)) unlink($destFile);

            if ($all) {
                $cmd = sprintf('cd %s && tar -zcf %s %s',
                    escapeshellarg(Config::getBasePath()),
                    escapeshellarg(basename($destFile)),
                    escapeshellarg(basename(Path::createDataPath()))
                );
            } else {
                $cmd = sprintf('cd %s && tar --exclude=%s -zcf %s %s',
                    escapeshellarg(Config::getBasePath()),
                    escapeshellarg('private'),
                    escapeshellarg(basename($destFile)),
                    escapeshellarg(basename(Path::createDataPath()))
                );
            }

            $pid = trim(exec("$cmd > /dev/null 2>&1 & echo $!"));
            //$pid = trim(shell_exec("$cmd > /dev/null 2>&1 & echo $!"));
            if(empty($pid)) {
                throw new \Tk\Exception("Error creating backup file");
            }
            // Return the PID and destFile as JSON
            return new JsonResponse((object)[
                'pid' => $pid,
                'filename' => basename($destFile),
            ]);
        }
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
