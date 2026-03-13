<?php
namespace Bs\Console;

use Bs\Db\SqlMigrate;
use Bs\Factory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tk\Config;
use Tk\Db;
use Tk\Exception;
use Tk\Path;
use Tk\System;

class InstallOld extends Console
{
    protected string $error = '';

    protected function configure(): void
    {
        $this->setName('install-old')
            ->setAliases(['oins'])
            ->setDescription('Install the site (deprecated)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        set_time_limit(0);

        $basePath = Config::getBasePath();
        $configInFile = $basePath . '/config.php.in';
        $configFile = $basePath . '/config.php';
        $htInFile = $basePath . '/.htaccess.in';
        $htFile = $basePath . '/.htaccess';

        $hasConfig = is_file($configFile);
        $hasHt = is_file($htFile);

        $configVars = [];

        try {
            $this->writePackageInfo();

            if (!$hasHt) {
                // Prompt to create new .htaccess
                $configVars['base.url'] = $this->createHtaccesFile($htInFile, $htFile);
            }

            if (!$hasConfig) {
                // Prompt to create new config.php file
                $this->createConfigFile($configInFile, $configFile, $configVars);

                // init new config file
                Config::destroy();
            }

            // Create new DB connection if one exists
            $config = Config::instance();
            Db::connect($config->get('db.mysql', ''));

            // Create the `/data` path if not exists
            $dataPath = Path::createDataPath();
            if (!is_dir($dataPath)) {
                $this->writeGreen('Creating data directory: ' . $dataPath);
                mkdir($dataPath, 0777, true);
            }

            // If no tables exist, install the new DB with any required prompts
            $drop = false;
            $tables = Db::getTableList();
            if ($input->isInteractive() && count($tables)) {
                $drop = $this->askConfirmation('Replace the existing database. WARNING: Existing data tables will be deleted! [N]: ', false);
            }
            if ($drop) {
                $exclude = [Db\MySqlSession::$DB_TABLE];
                Db::dropAllTables(true, $exclude);
            }

            $this->siteDbMigration();

        } catch(\Exception $e) {
            $this->writeError($e->getMessage());
            return Command::FAILURE;
        }

        $this->write('Installation Complete!!!');
        return  Command::SUCCESS;
    }

    private function siteDbMigration(): void
    {
        // Update Database tables
        $tables = Db::getTableList();
        if (count($tables)) {
            $this->writeGreen('Database Upgrade:');
        } else {
            $this->writeGreen('Database Install:');
        }

        // migrate site sql files
        if (!SqlMigrate::migrateAll([$this, 'writeGrey'])) {
            throw new Exception("Failed to migrate files");
        }

        $this->writeGreen('Purging caches');

        if (class_exists(Factory::class)) {
            Factory::instance()->purgeCache();
            Factory::instance()->getCompiledRoutes(true);
        }

        $this->writeGreen('Database Migration Complete');
    }


    private function createHtaccesFile(string $htInFile, string $htFile): string
    {
        $defaultUrl = System::discoverBaseUrl() ?: '/';
        $baseurl = $this->ask('What is the base URL path [' . $defaultUrl . ']: ',
            function ($value) use ($defaultUrl) {
                if (!$value) $value = $defaultUrl;
                return trim($value);
            }
        );

        $this->writeGreen('Creating .htaccess file');
        $buf = strval(file_get_contents($htInFile));
        $buf = str_replace('RewriteBase /', 'RewriteBase ' . $baseurl, $buf);
        file_put_contents($htFile, $buf);

        return $baseurl;
    }

    private function createConfigFile(string $configInFile, string $configFile, array $configVars = []): void
    {
        $configVars['system.encrypt'] = hash('sha256', 'Tropotek_'.microtime());
        $configVars['db.default.type'] = 'mysql';

        $configContents = strval(file_get_contents($configInFile));

        // prompt for DB creds
        $this->writeGreen('Please answer the following questions to setup your new site configuration.');

        $configVars['db.default.host'] = $this->ask('Set the DB hostname [localhost]: ',
            function ($value) {
                if (!$value) $value = 'localhost';
                return $value;
            });
        $configVars['db.default.port'] = $this->ask('Set the DB port [3306]: ',
            function ($value) {
                if (!$value) $value = '3306';
                return $value;
            });
        $configVars['db.default.name'] = $this->ask('Set the DB name: ',
            function ($value) {
                if (!$value) throw new \Exception('Please enter the DB name to use.');
                return $value;
        });
        $configVars['db.default.user'] = $this->ask('Set the DB user: ',
            function ($value) {
                if (!$value) throw new \Exception('Please enter the DB username.');
                return $value;
        });
        $configVars['db.default.pass'] = $this->ask('Set the DB password: ',
            function ($value) { if (!$value) throw new \Exception('Please enter the DB password.');
            return $value;
        });

        $configVars['db.mysql'] = sprintf('%s:%s/%s/%s/%s',
            $configVars['db.default.host'],
            $configVars['db.default.port'],
            $configVars['db.default.user'],
            $configVars['db.default.pass'],
            $configVars['db.default.name'],
        );

        // update the config contents string
        foreach ($configVars as $k => $v) {
            $configContents = str_replace("{{$k}}", $v, $configContents);
        }

        $this->writeGreen('Saving config.php');
        file_put_contents($configFile, $configContents);
    }

    protected function writePackageInfo(): void
    {
        $pkg = (object)System::getComposerJson();

        $name = substr($pkg->name, strrpos($pkg->name, '/')+1);
        $version = System::getVersion();
        $releaseDate = $pkg->time;
        $year = date('Y', \DateTime::createFromFormat('Y-m-d', $pkg->time)->getTimestamp());
        $desc = wordwrap($pkg->description, 45, "\n               ");
        $authors = [];
        foreach ($pkg->authors as $auth) {
            $authors[] = $auth['name'] ?? '';
        }
        $authors = implode(', ', $authors);

        $head = <<<STR
        -----------------------------------------------------------
               $name - (c) tropotek.com $year
        -----------------------------------------------------------
          Project:     $name
          Version:     $version
          Released:    $releaseDate
          Author:      $authors
          Description: $desc
        -----------------------------------------------------------
        STR;
        $this->write($head);
    }

}
