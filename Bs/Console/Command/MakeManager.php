<?php
namespace Bs\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeManager extends MakeInterface
{

    protected function configure(): void
    {
        $this->setName('make-manager')
            ->setAliases(array('mg'))
            ->setDescription('Create a PHP Controller Manager Class from the DB schema');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);
        $this->makeManager();

        return Command::SUCCESS;
    }

}
