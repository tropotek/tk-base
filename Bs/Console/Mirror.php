<?php
namespace Bs\Console;

use Bs\Db\SqlMigrate;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;
use Tk\Config;
use Tk\Encrypt;
use Tk\Exception;
use Tk\Log;
use Tk\Path;
use Tk\Uri;
use Tk\Db;

class Mirror extends Console
{
    protected string $error = '';

    protected function configure(): void
    {
        $this->setName('mirror')
            ->setAliases(['mi'])
            ->setDescription('Mirror the DB data from the Src site. [Admin Only]')
            ->addArgument('username', InputArgument::REQUIRED, 'User with mirror access the remote site')
            ->addOption('password', 'p', InputArgument::OPTIONAL, 'password for the remote site', '')
            ->addOption('no-migrate', 'x', InputOption::VALUE_NONE, 'Do not execute/migrate the downloaded sql file into the DB')
            ->addOption('save', 's', InputOption::VALUE_NEGATABLE, 'Save downloaded sql file to the current directory.')
            ->addOption('refresh', 'C', InputOption::VALUE_NEGATABLE, 'Force fresh download of the remote DB file.')
            ->addOption('noverify', 'N', InputOption::VALUE_NONE, 'Disable verify SSL')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        set_time_limit(0);

        try {
            $config = $this->getConfig();
            if (!$config->get('db.mirror.enabled', true)) {
                $this->writeError('This command is disabled for this site. Enable it in the config file: db.mirror.enabled = true');
                return Command::FAILURE;
            }
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

            $dstBakFile = Path::createTempPath('/dst-bak.sql');
            $newZipFile = Path::createTempPath('/' . \Tk\Date::create()->format(\Tk\Date::FORMAT_ISO_DATE) . '-tmpl.sql.gz');
            $newSqlFile = substr($newZipFile, 0, -3);

            $options = Db::parseDsn($this->getConfig()->get('db.mysql'));
            // must exclude _migrate table for below migrate cmd to work
            $options['exclude'] = ['_session'];
            $username = trim($input->getArgument('username'));

            if (!is_file($newSqlFile) || $input->getOption('refresh')) {
                $this->writeComment('Downloading fresh mirror file');

                // Delete existing cached files
                $list = glob(Path::createTempPath('/*-tmpl.sql*'));
                if (is_array($list)) {
                    foreach ($list as $file) {
                        if (is_file($file)) unlink($file);
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

                // get a copy of the remote DB to be mirrored
                $mirrorUrl = Uri::create(rtrim($this->getConfig()->get('db.mirror.url'), '/') . '/util/mirror')
                    ->set('a', 'db')
                    ->set('u', $username)
                    ->set('p', $password)
                    ->withScheme('https');
                $this->writeComment("Requesting Data");

                if (!$this->postRequest($mirrorUrl, $newZipFile, !$input->getOption('noverify'))) {
                    $this->writeError("Error requesting mirror: " . $this->error);
                    return Command::FAILURE;
                }
                if (!is_file($newZipFile)) {
                    $this->writeError("Error downloading mirror");
                    return Command::FAILURE;
                }
            } else {
                $this->writeComment('Using existing mirror file');
            }

            // dont execute if no-migrate flag set
            if (!$input->getOption('no-migrate')) {

                // Prevent accidental writing to live DB
                $this->writeComment('Backup this DB to file: ' . $dstBakFile);
                Db\DbBackup::save($dstBakFile, $options);
                if (!is_file($dstBakFile)) {
                    $this->writeError("Error backing up system DB");
                    return Command::FAILURE;
                }

                $this->write('Drop this DB tables');
                Db::dropAllTables(true, $options['exclude']);

                if (!is_file($newZipFile)) $newZipFile = $newSqlFile;
                if (!is_file($newZipFile)) {
                    $this->writeError("Error executing mirror");
                    return Command::FAILURE;
                }
                $this->write('Import mirror file to this DB');
                Db\DbBackup::restore($newZipFile, $options);

                // migrate site sql files
                if (!SqlMigrate::migrateAll([$this, 'write'])) {
                    $this->writeError("Failed to migrate files");
                    return Command::FAILURE;
                }
            }

            // save file if requested
            if ($input->getOption('save')) {
                $path = getcwd();
                if (!str_ends_with(strval($path), '.sql')) $path = $path . '/' . basename($newSqlFile);
                copy($newSqlFile, strval($path));
            }

            //if (is_file($newSqlFile)) unlink($newSqlFile);
            if (is_file($dstBakFile)) unlink($dstBakFile);

        } catch(\Exception $e) {
            $this->writeError($e->getMessage());
            return Command::FAILURE;
        }

        $this->write('Complete!!!');
        return  Command::SUCCESS;
    }

    protected function postRequest(Uri|string $srcUrl, string $filename, bool $verifyssl = true): bool
    {
        $secret = $this->getConfig()->get('db.mirror.secret', '');
        if (empty($secret)) {
            $this->error = "Invalid API secret";
            return false;
        }

        $enc = Encrypt::create($secret);
        $ok = true;
        $srcUrl = Uri::create($srcUrl)->withScheme('https');
        $srcUrl->set('u', $enc->basicEncrypt($srcUrl->get('u')));
        $srcUrl->set('p', $enc->basicEncrypt($srcUrl->get('p')));

        // convert query vals to post vals
        $query = $srcUrl->getQuery();
        $srcUrl->reset();

        $fp = fopen($filename, "w");
        if ($fp === false) {
            Log::error("Cannot save filename");
            return false;
        }
        $curl = curl_init($srcUrl->toString());
        if ($curl === false) {
            Log::error("Cannot open mirror Url");
            return false;
        }

        $opts = [
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS     => $query,
            CURLOPT_FILE           => $fp,
            // CURLOPT_CONNECTTIMEOUT => 60,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_HTTPHEADER     => [
                "authorization-key: " . $secret,
            ],
            CURLOPT_USERAGENT      => Uri::USERAGENT,
        ];
        if (!$verifyssl) {
            $opts[CURLOPT_SSL_VERIFYHOST] = false;
            $opts[CURLOPT_SSL_VERIFYPEER] = false;
        }
        // @phpstan-ignore-next-line
		curl_setopt_array($curl, $opts);

        curl_exec($curl);
        if(curl_error($curl) || curl_getinfo($curl, CURLINFO_RESPONSE_CODE) != 200) {
            $this->error = curl_error($curl);
            Log::error("Error requesting mirror [" . curl_getinfo($curl, CURLINFO_RESPONSE_CODE) . ']: ' . $this->error);
            $ok = false;
        }
        curl_close($curl);
        fclose($fp);

        return $ok;
    }

}
