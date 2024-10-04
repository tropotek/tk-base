<?php
namespace Bs\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Bs\Console\Console;
use Tk\Config;
use Tk\Log;
use Tk\Uri;
use Tk\Db;

/**
 *
 */
class Mirror extends Console
{

    protected function configure(): void
    {
        $this->setName('mirror')
            ->setAliases(['mi'])
            ->setDescription('Mirror the data and files from the Live site')
            ->addArgument('username', InputArgument::REQUIRED, 'User with admin access the remote site')
            ->addOption('no-cache', 'C', InputOption::VALUE_NONE, 'Force downloading of the live DB. (Cached for the day)')
            ->addOption('no-sql', 'S', InputOption::VALUE_NONE, 'Do not execute the downloaded sql file')
            //->addOption('no-dev', 'f', InputOption::VALUE_NONE, 'Do not execute the dev sql file')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
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

            $backupSqlFile = Config::makePath(Config::getTempPath() . '/tmpt.sql');
            $mirrorSqlFile = Config::makePath(Config::getTempPath() . '/' . \Tk\Date::create()->format(\Tk\Date::FORMAT_ISO_DATE) . '-tmpl.sql.gz');

            // Delete live cached files
            $list = glob(Config::makePath(Config::getTempPath() . '/*-tmpl.sql.gz'));
            foreach ($list as $file) {
                if ($input->getOption('no-cache') || $file != $mirrorSqlFile) {
                    if (is_file($file)) unlink($file);
                }
            }

            $options = Db::parseDsn($this->getConfig()->get('db.mysql'));
            $options['exclude'] = [$config->get('session.db_table')];
            $secret   = $this->getConfig()->get('db.mirror.secret');
            $username = trim($input->getArgument('username'));

            if (!$input->getOption('no-sql')) {
                if (!is_file($mirrorSqlFile) || $input->getOption('no-cache')) {
                    $this->writeComment('Download fresh mirror file: ' . $mirrorSqlFile);
                    if (is_file($mirrorSqlFile)) unlink($mirrorSqlFile);

                    // get a copy of the remote DB to be mirrored
                    $mirrorUrl = Uri::create(rtrim($this->getConfig()->get('db.mirror.url'), '/') . '/util/mirror')
                        ->set('action', 'db')
                        ->set('secret', $secret)
                        ->set('un', $username);
                    Log::debug("Requesting Data: {$mirrorUrl}");
                    $this->postRequest($mirrorUrl, $mirrorSqlFile);
                } else {
                    $this->writeComment('Using existing mirror file: ' . $mirrorSqlFile);
                }

                // Prevent accidental writing to live DB
                $this->writeComment('Backup this DB to file: ' . $backupSqlFile);
                Db\DbBackup::save($backupSqlFile, $options);

                $this->write('Drop this DB tables');
                Db::dropAllTables(true, $options['exclude'] ?? []);

                $this->write('Import mirror file to this DB');
                Db\DbBackup::restore($mirrorSqlFile, $options);

                // Execute static files
                //SqlMigrate::migrateStatic([$this, 'writeGreen']);

                // setup dev environment if site in dev mode
                //SqlMigrate::migrateDev([$this, 'writeBlue']);

                //unlink($backupSqlFile);
            }

        } catch(\Exception $e) {
            $this->writeError($e->getMessage());
            return Command::FAILURE;
        }


        $this->write('Complete!!!');
        return  Command::SUCCESS;
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
