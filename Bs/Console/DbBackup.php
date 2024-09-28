<?php
namespace Bs\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Tk\FileUtil;
use Tk\Db;

/**
 * Makes a compressed tgz file of the sites database
 */
class DbBackup extends Console
{

    protected function configure(): void
    {
        $this->setName('dbbackup')
            ->setAliases(['dbb'])
            ->setDescription('Call this to dump a copy of the Database sql to stdout or a file if an argument is given')
            ->addArgument('output', InputArgument::OPTIONAL, 'A file path to dump the SQL to.', null)
            ->addArgument('date_format', InputArgument::OPTIONAL, 'Auto filename generated based on date when a directory is supplied as the output. See http://php.net/manual/en/function.date.php', 'D')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $outfile = $input->getArgument('output');
            $options = Db::parseDsn($this->getConfig()->get('db.mysql'));

            if ($outfile) {
                if (is_dir($outfile)) {
                    $outfile = $outfile . '/' . \Tk\Date::create()->format($input->getArgument('date_format')) . '.sql';
                    $this->writeComment('  - Saving SQL to: ' . $outfile);
                } else {
                    $this->writeComment('  - Creating directory: ' . dirname($outfile));
                    FileUtil::mkdir(dirname($outfile));
                }
                Db\DbBackup::save($outfile, $options);
            } else {
                $this->write(Db\DbBackup::dump($options));
            }
        } catch (\Exception $e) {
            $this->writeError($e->getMessage());
            return Command::FAILURE;
        }
        return Command::SUCCESS;
    }

}
