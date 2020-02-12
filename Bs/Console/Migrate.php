<?php
namespace Bs\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Tk\Db\Pdo;
use Tk\Util\SqlMigrate;

/**
 * @author Michael Mifsud <info@tropotek.com>
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
            ->setAliases(array('mig'))
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

        // Migrate new SQL files
        $migrate = new SqlMigrate($db);
        $migrate->setTempPath($this->getConfig()->getTempPath());
        $migrateList = array('App Sql' => $this->getConfig()->getSrcPath() . '/config');
        if ($this->getConfig()->get('sql.migrate.list')) {
            $migrateList = $this->getConfig()->get('sql.migrate.list');
        }

        $migrate->migrateList($migrateList, function (string $str, SqlMigrate $m) use ($output) {
            $output->writeln($str);
        });



    }

}
