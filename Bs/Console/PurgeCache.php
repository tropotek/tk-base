<?php
namespace Bs\Console;

use Bs\Factory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tk\FileUtil;
use Tk\Path;

/**
 * Clean out the site filesystem cache directory
 */
class PurgeCache extends Console
{

    protected function configure(): void
    {
        $this->setName('purge-cache')
            ->setAliases(['pc'])
            ->setDescription('Purge the filesystem cache directory');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            Factory::instance()->purgeCache();
        } catch (\Exception $e) {
            $this->writeError($e->getMessage());
            return Command::FAILURE;
        }
        return Command::SUCCESS;
    }

}
