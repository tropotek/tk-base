<?php
namespace Bs\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2017 Michael Mifsud
 */
class DbBackup extends \Tk\Console\Console
{

    /**
     *
     */
    protected function configure()
    {
        $this->setName('dbbackup')
            ->setDescription('Call this to dump a copy of the Database sql to stdout or a file if an argument is given')
            ->addArgument('output', InputArgument::OPTIONAL, 'A file path to dump the SQL to.', null)
            ->addOption('date_format', 'f', InputArgument::OPTIONAL, 'Auto filename generated based on date when a directory is supplied as the output. See http://php.net/manual/en/function.date.php', 'D')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Tk\Db\Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $config = \App\Config::getInstance();
        $db = $config->getDb();
        $options = array();
        $outfile = $input->getArgument('output');
        $bak = new \Tk\Util\SqlBackup($db);

        if ($outfile) {
            if (is_dir($outfile)) {
                $outfile = $outfile . '/' . \Tk\Date::create()->format($input->getOption('date_format')) . '.sql'; // a backup for each day of the Week before overrwriting
                $this->writeComment('  - Saving SQL to: ' . $outfile);
            } else {
                $this->writeComment('  - Creating directory: ' . dirname($outfile));
                if (!is_dir(dirname($outfile))) {
                    mkdir(dirname($outfile), 0777, true);
                }
            }
            $bak->save($outfile, $options);
        } else {
            //TODO: This could be a rather large dump and could produce memory errors????
            $this->write($bak->dump($options));
        }
    }

}
