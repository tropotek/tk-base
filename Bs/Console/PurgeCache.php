<?php
namespace Bs\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tk\Cache\Cache;

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
            Cache::instance()->purge();
        } catch (\Exception $e) {
            $this->writeError($e->getMessage());
            return Command::FAILURE;
        }
        return Command::SUCCESS;
    }

}
