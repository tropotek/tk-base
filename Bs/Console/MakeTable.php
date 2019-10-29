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
class MakeTable extends MakerIface
{

    /**
     *
     */
    protected function configure()
    {
        $this->setName('make-table')
            ->setAliases(array('mt'))
            ->setDescription('Create a PHP Manager Table Class from the DB schema');
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
        $this->makeTable();

//        $config = \Bs\Config::getInstance();
//        $phpFile = $config->getSitePath() . '/src/' . str_replace('\\', '/', $this->getGen()->getTableNamespace()) . '/' . $this->getGen()->getClassName() . '.php';
//        //$phpFile = $config->getSitePath() . '/src/App/Table/' . $gen->getClassName() . '.php';
//        if (!$input->getOption('overwrite'))
//            $phpFile = $this->makeUniquePhpFilename($phpFile);
//        $tableCode = $this->getGen()->makeTable();
//
//        if (!is_dir(dirname($phpFile))) {
//            $this->writeComment('Creating Path: ' . dirname($phpFile));
//            mkdir(dirname($phpFile), 0777, true);
//        }
//
//        $this->writeComment('Writing: ' . $phpFile);
//        file_put_contents($phpFile, $tableCode);

    }
}
