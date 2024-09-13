<?php
namespace Bs\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeMapper extends MakeInterface
{

    protected function configure(): void
    {
        $this->setName('make-mapper')
            ->setAliases(['mmp'])
            ->setDescription('Create a PHP Model Mapper Class from the DB schema');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);
        $this->makeMapper();

        return Command::SUCCESS;
    }

}
