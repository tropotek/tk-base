<?php
namespace Bs\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Tk\Db\Pdo;
use Tk\Util\SqlMigrate;

/**
 * @author Michael Mifsud <http://www.tropotek.com/>
 * @see http://www.tropotek.com/
 * @license Copyright 2017 Michael Mifsud
 */
class Migrate extends Iface
{

    /**
     *
     */
    protected function configure()
    {
        $this->setName('migrate')
            ->setAliases(array('mgt'))
            ->setDescription('Migrate the DB file for this project and its dependencies');
        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $db = Pdo::getInstance('db', $this->getConfig()->getGroup('db', true));
        $this->getConfig()->setDb($db);

        $drop = false;
        $tables = $db->getTableList();

        if (count($tables))
            $drop = $this->askConfirmation('Replace the existing database. WARNING: Existing data tables will be deleted! [N]: ', false);

        if ($drop) {
            $exclude = array();
            if ($this->getConfig()->isDebug()) {
                $exclude = array(\Tk\Session\Adapter\Database::$DB_TABLE);
            }
            $db->dropAllTables(true, $exclude);
            $this->write('Database Install...');
        } else {
            $this->write('Database Upgrade...');
        }

        //$tables = $db->getTableList();

        // Migrate new SQL files
        $migrate = new SqlMigrate($db);
        $migrate->setTempPath($this->getConfig()->getTempPath());
        $migrateList = array('App Sql' => $this->getConfig()->getSrcPath() . '/config');
        if ($this->getConfig()->get('sql.migrate.list')) {
            $migrateList = $this->getConfig()->get('sql.migrate.list');
        }

        $mm = $this;
        $migrate->migrateList($migrateList, function (string $str, SqlMigrate $m) use ($output, $mm) {
            $mm->write($str);
        });

        // Run all static scripts views.sql, triggers.sql, procedures.sql, functions.sql
        $config = $this->getConfig();
        $dbBackup = \Tk\Util\SqlBackup::create($db);
        $staticFiles = ['views.sql', 'triggers.sql', 'procedures.sql', 'functions.sql'];
        foreach ($staticFiles as $file) {
            $path = "{$config->getSitePath()}/src/config/sql/{$file}";
            if (is_file($path)) {
                $this->writeGreen('Applying ' . $file);
                $dbBackup->restore($path);
            }
        }

        // TODO: I do not think this should be run during migration only on the mirror command.
        //       revert this if needed
//        $debugSqlFile  = $config->getSitePath() . '/bin/assets/debug.sql';
//        if ($config->isDebug() && is_file($debugSqlFile)) {
//            $this->writeBlue('Apply dev sql updates');
//            $dbBackup->restore($debugSqlFile);
//        }

        $this->write('Database Migration Complete.');
        $this->write('Open the site in a browser to complete the site setup: ' . \Tk\Uri::create('/')->toString());
    }

}
