<?php
namespace Bs\Console;

use Bs\Registry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tk\Config;
use Tk\FileUtil;
use Tk\Uri;

/**
 * Copy all files in the remote /data folder excluding site caching and temp files
 * to a local destination folder. If the dest exists it will be moved `_data1`, `_data2`, ... `_dataN`
 *
 * In order for this to work the following config settings must be enabled/added:
 * ```
 *      $config['db.mirror.secret'] = '';
 *      $config['db.mirror.url'] = '';
 * ```
 */
class MirrorData extends Console
{

    protected function configure(): void
    {
        $this->setName('mirror-data')
            ->setAliases(['md'])
            ->setDescription('Copy remote `/data` folder to specified location')
            ->addArgument('username', InputArgument::REQUIRED, 'User with admin access the remote site')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $config = $this->getConfig();
        if (!Config::isDev()) {
            $this->writeError('Only run this command in a dev environment.');
            return Command::FAILURE;
        }
        if (!$this->getConfig()->get('db.mirror.secret', false)) {
            $this->writeError('Secret key not valid: ' . $this->getConfig()->get('db.mirror.secret'));
            return Command::FAILURE;
        }
        if (!$config->get('db.mirror.url', false)) {
            $this->writeError('Invalid source mirror URL: ' . $config->get('db.mirror.url'));
            return Command::FAILURE;
        }

        if (getcwd() != Config::getBasePath()) {
            $this->writeError('Run this command from the site root path');
            return Command::FAILURE;
        }

        $secret       = $this->getConfig()->get('db.mirror.secret');
        $username     = trim($input->getArgument('username'));
        $tempDataFile = Config::makePath('/' . \Tk\Date::create()->format(\Tk\Date::FORMAT_ISO_DATE) . '-data.tgz');

        $this->write('Downloading live data files...[Please wait]');
        if (is_file($tempDataFile)) unlink($tempDataFile);

        $mirrorUrl = Uri::create($this->getConfig()->get('db.mirror.url') . '/util/mirror')
            ->set('action', 'file')
            ->set('secret', $secret)
            ->set('un', $username);

        $error = $this->postRequest($mirrorUrl, $tempDataFile);
        if (!empty($error)) {
            $this->writeError('Error downloading data archive');
            return Command::FAILURE;

        }
        $this->write('Download Complete!');

        $tmpgz = Config::makePath('/tmpData');
        if (is_dir($tmpgz)) {
            FileUtil::rmdir($tmpgz);
        }
        FileUtil::mkdir($tmpgz);

        $this->write('Extracting files to: ' . $tmpgz);
        $cmd = sprintf('cd %s && tar zxf %s -C %s',
            escapeshellarg(Config::getBasePath()),
            escapeshellarg(basename($tempDataFile)),
            escapeshellarg($tmpgz)
        );
        exec($cmd, $out, $ret);
        if ($ret != self::SUCCESS) {
            $this->writeError('Error extracting data archive');
            return Command::FAILURE;
        }

        $dest = '/data';
        if (is_dir(Config::makePath($dest))) {
            // move existing dir to bak dest
            $bak = $this->uniqueDir($dest);
            $this->write('Move current data files to backup location: ' . $bak);
            $cmd = sprintf('mv %s %s ', escapeshellarg(Config::makePath($dest)), escapeshellarg(Config::makePath($bak)));
            exec($cmd, $out, $ret);
            if ($ret != self::SUCCESS) {
                $this->writeError('Error moving old data directory');
                return Command::FAILURE;
            }
        }

        $this->write('Move extracted data files to: ' . Config::makePath($dest));
        $cmd = sprintf('mv %s %s ', escapeshellarg($tmpgz.'/data'), escapeshellarg(Config::makePath($dest)));
        exec($cmd, $out, $ret);
        if ($ret != self::SUCCESS) {
            $this->writeError('Error moving old data directory');
            return Command::FAILURE;
        }

        FileUtil::rmdir($tempDataFile);
        FileUtil::rmdir($tmpgz);

        $this->write('Complete!!!');
        return Command::SUCCESS;
    }

    protected function uniqueDir(string $dir): string
    {
        $num = 0;
        $path = $dir;
        while(is_dir(Config::makePath($path))) {
            $num++;
            $path = sprintf('%s%s%s%s', DIRECTORY_SEPARATOR, ($num > 0 ? '_' : ''), trim($dir, DIRECTORY_SEPARATOR), ($num > 0 ? $num : ''));
        }
        return $path;
    }

    protected function postRequest(Uri|string $srcUrl, string $destPath): bool
    {
        $error = false;
        $srcUrl = Uri::create($srcUrl)->setScheme(Uri::SCHEME_HTTP_SSL);
        $query = $srcUrl->getQuery();
        $srcUrl->reset();

        $fp = fopen($destPath, 'w');
        $curl = curl_init($srcUrl->toString());
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $query);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_FILE, $fp);

        curl_exec($curl);
        if(curl_error($curl) || curl_getinfo($curl, CURLINFO_RESPONSE_CODE) != 200) {
            $error = true;
        }
        curl_close($curl);
        fclose($fp);
        return $error;
    }
}
