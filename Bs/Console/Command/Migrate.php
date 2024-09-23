<?php
namespace Bs\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Bs\Console\Console;
use Bs\Util\Db\SqlMigrate;
use Tk\Config;
use Tk\Db;

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
