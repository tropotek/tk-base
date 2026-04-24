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

class Deploy extends Console
{
    protected string $error = '';

    protected function configure(): void
    {
        $this->setName('deploy')
            ->setAliases(['dpy'])
            ->setDescription('Docker deploy script')
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

            // migrate DB
            $tables = Db::getTableList();
            if (count($tables)) {
                if (!SqlMigrate::migrateAll([$this, 'writeGrey'])) {
                    throw new Exception("Failed to migrate files");
                }
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

        return  Command::SUCCESS;
    }

}
