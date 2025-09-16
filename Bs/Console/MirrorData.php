<?php
namespace Bs\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Tk\Config;
use Tk\Encrypt;
use Tk\FileUtil;
use Tk\Log;
use Tk\Path;
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
    protected string $error = '';

    protected function configure(): void
    {
        $this->setName('mirror-data')
            ->setAliases(['md'])
            ->setDescription('Copy remote `/data` folder to specified location')
            ->addArgument('username', InputArgument::REQUIRED, 'User with admin access the remote site')
            ->addOption('password', 'p', InputArgument::OPTIONAL, 'password for the remote site', '')
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'download all data files (including /private)')
            ->addOption('noverify', 'N', InputOption::VALUE_NONE, 'Disable verify SSL')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        set_time_limit(0);

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

        if ($input->getOption('all')) {
            $confirm = $this->askConfirmation("Warning: Replace the '/data' folder, existing data files will be lost, continue? [N]: ", false);
            if (!$confirm) {
                $this->output->writeln("Mirror terminated.");
                return self::SUCCESS;
            }
        }

        $password = $input->getOption('password');
        while(empty($password)) {
            $q = new Question('Enter password: ', '');
            $q->setHidden(true);
            $q->setTrimmable(true);
            /** @phpstan-ignore-next-line */
            $password = $this->getHelper('question')->ask($input, $output, $q);
            if (empty($password)) {
                $this->writeError('Password cannot be empty.');
            }
        }

        $username     = trim($input->getArgument('username'));
        $dstDataFile = Path::create('/dst-' . \Tk\Date::create()->format(\Tk\Date::FORMAT_ISO_DATE) . '-data.tgz');

        $this->write('Downloading live data files...[Please wait]');
        if (is_file($dstDataFile)) unlink($dstDataFile);

        $mirrorUrl = Uri::create($this->getConfig()->get('db.mirror.url') . '/util/mirror')
            ->set('a', 'file')
            ->set('u', $username)
            ->set('p', $password);
        if ($input->getOption('all')) {
            $mirrorUrl->set('all', '1');
        }

        if (!$this->postRequest($mirrorUrl, $dstDataFile, !$input->getOption('noverify'))) {
            $this->writeError('Error requesting mirror archive');
            return Command::FAILURE;

        }
        if (!is_file($dstDataFile)) {
            $this->writeError('Error mirror data archive');
            return Command::FAILURE;
        }
        $this->write('Download Complete!');

        $tmpgz = Path::create('/tmpData');
        if (is_dir($tmpgz)) {
            FileUtil::rmdir($tmpgz);
        }
        FileUtil::mkdir($tmpgz);

        $this->write('Extracting files to: ' . $tmpgz);
        $cmd = sprintf('cd %s && tar zxf %s -C %s',
            escapeshellarg(Config::getBasePath()),
            escapeshellarg(basename($dstDataFile)),
            escapeshellarg($tmpgz)
        );
        exec($cmd, $out, $ret);
        if ($ret != self::SUCCESS) {
            $this->writeError('Error extracting data archive');
            return Command::FAILURE;
        }

        $dest = '/data';
        $bak  = '';
        if (is_dir(Path::create($dest))) {
            // move existing dir to bak dest
            $bak = $this->uniqueDir($dest);
            $this->write('Move current data files to backup location: ' . $bak);
            $cmd = sprintf('mv %s %s ',
                escapeshellarg(Path::create($dest)),
                escapeshellarg(Path::create($bak))
            );
            exec($cmd, $out, $ret);
            if ($ret != self::SUCCESS) {
                $this->writeError('Error moving old data directory');
                return Command::FAILURE;
            }
        }

        $this->write('Move extracted data files to: ' . Path::create($dest));
        $cmd = sprintf('mv %s %s ',
            escapeshellarg($tmpgz.'/data'),
            escapeshellarg(Path::create($dest))
        );
        exec($cmd, $out, $ret);
        if ($ret != self::SUCCESS) {
            $this->writeError('Error moving old data directory');
            return Command::FAILURE;
        }

        if (!$input->getOption('all') && is_dir(Path::create($bak.'/private'))) {
            $this->write('Restoring private files');
            $cmd = sprintf('cp %s %s -R',
                escapeshellarg(Path::create($bak . '/private')),
                escapeshellarg(Path::create($dest) . '/private')
            );
            exec($cmd, $out, $ret);
            if ($ret != self::SUCCESS) {
                $this->writeError('Error restoring /private files');
                return Command::FAILURE;
            }
        }

        FileUtil::rmdir($dstDataFile);
        FileUtil::rmdir($tmpgz);
        FileUtil::rmdir(Path::create($bak));

        $this->write('Complete!!!');
        return Command::SUCCESS;
    }

    protected function uniqueDir(string $dir): string
    {
        $num = 0;
        $path = $dir;
        while(is_dir(Path::create($path))) {
            $num++;
            $path = sprintf('%s%s%s%s', DIRECTORY_SEPARATOR, '_', trim($dir, DIRECTORY_SEPARATOR), $num);
        }
        return $path;
    }

    protected function postRequest(Uri|string $srcUrl, string $filename, bool $verifyssl = true): bool
    {
        $secret = $this->getConfig()->get('db.mirror.secret', '');
        if (empty($secret)) {
            $this->error = "Invalid API secret";
            return false;
        }

        $enc = Encrypt::create($secret);
        $ok     = true;
        $srcUrl = Uri::create($srcUrl)->withScheme('https');
        $srcUrl->set('u', $enc->basicEncrypt($srcUrl->get('u')));
        $srcUrl->set('p', $enc->basicEncrypt($srcUrl->get('p')));

        $query  = $srcUrl->getQuery();
        $srcUrl->reset();

        $fp = fopen($filename, "w");
        if ($fp === false) {
            Log::error("Cannot open filename: $filename");
            return false;
        }
        $curl = curl_init($srcUrl->toString());
        if ($curl === false) {
            Log::error("Cannot open Url: $srcUrl");
            return false;
        }

        $opts = [
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS     => $query,
            CURLOPT_FILE           => $fp,
            CURLOPT_HTTPHEADER     => [
                "authorization-key: " . $secret,
            ],
            CURLOPT_USERAGENT      => Uri::USERAGENT,
        ];
        if (!$verifyssl) {
            $opts[CURLOPT_SSL_VERIFYHOST] = false;
            $opts[CURLOPT_SSL_VERIFYPEER] = false;
        }
        curl_setopt_array($curl, $opts);

        curl_exec($curl);
        if(curl_error($curl) || curl_getinfo($curl, CURLINFO_RESPONSE_CODE) != 200) {
            $this->error = curl_error($curl);
            $ok = false;
        }
        curl_close($curl);
        fclose($fp);

        return $ok;
    }
}
