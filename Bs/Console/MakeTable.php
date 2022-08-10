<?php
namespace Bs\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * @author Michael Mifsud <http://www.tropotek.com/>
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

    }
}
