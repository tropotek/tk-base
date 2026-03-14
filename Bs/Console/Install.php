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

class Install extends Console
{
    protected string $error = '';

    protected function configure(): void
    {
        $this->setName('install')
            ->setAliases(['ins'])
            ->setDescription('Install the site')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $basePath = Config::getBasePath();
        $configInFile = $basePath . '/config.php.in';
        $configFile = $basePath . '/config.php';

        // create a config file if missing
        if (!is_file($configFile)) {
            copy($configInFile, $configFile);
        }

        try {
            // composer install
            passthru('composer install --no-interaction --prefer-dist');

            // Create a new DB connection
            Config::destroy();
            $config = Config::instance();
            Db::connect($config->get('db.mysql', ''));

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

            if (!SqlMigrate::migrateAll([$this, 'writeGrey'])) {
                throw new Exception("Failed to migrate files");
            }

            // Purge caches
            if (class_exists(Factory::class)) {
                Factory::instance()->purgeCache();
                Factory::instance()->getCompiledRoutes(true);
            }

        } catch(\Exception $e) {
            $this->writeError($e->getMessage());
            return Command::FAILURE;
        }

        $this->write('Installation Complete!!!');
        return  Command::SUCCESS;
    }

}
