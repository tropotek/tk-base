<?php
namespace Bs\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Bs\Util\Db\SqlMigrate;
use Tk\Config;
use Tk\Db;

/**
 * Migrate the sites DB files
 * This will execute any SQL files that are not already listed in the _migrate table
 * Call this when upgrading the code and the database needs to be updated to the current release version
 *
 * In dev mode the /src/config/dev.php file is executed to setup a dev site
 *
 * Note: There is no rollback functions only forward migrations are supported.
 * Ensure you have a backup copy of the DB you are upgrading.
 */
class Migrate extends Console
{

    protected function configure(): void
    {
        $this->setName('migrate')
            ->setAliases(array('mgt'))
            ->setDescription('Migrate the DB file for this project and its dependencies');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);

            $drop = false;
            $tables = Db::getTableList();

            if (count($tables)) {
                $drop = $this->askConfirmation('Replace the existing database. WARNING: Existing data tables will be deleted! [N]: ', false);
            }

            if ($drop) {
                $exclude = [];
                if (Config::isDev()) {
                    $exclude = [$this->getConfig()->get('session.db_table')];
                }
                Db::dropAllTables(true, $exclude);
                $this->write('Mode: Install');
            } else {
                $this->write('Mode: Upgrade');
            }

            // Migrate new SQL files
            $this->write('Migration Starting.');

            // migrate site sql files
            SqlMigrate::migrateSite([$this, 'write']);

            // Execute static files
            SqlMigrate::migrateStatic([$this, 'writeGreen']);

            // setup dev environment if site in dev mode
            SqlMigrate::migrateDev([$this, 'writeBlue']);

            $this->write('Migration Complete.');
        } catch (\Exception $e) {
            $this->writeError($e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

}
