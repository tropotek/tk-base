<?php
namespace Bs\Console;

use Bs\Registry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tk\Config;
use Tk\Exception;

/**
 * Upgrade
 *
 * Downloads the current git repository code for the site
 * Then updates the dependencies using `composer update`
 *
 */
class Upgrade extends Console
{

    protected function configure(): void
    {
        $this->setName('upgrade')
            ->setAliases(['ug'])
            ->setDescription('Upgrade site from git repo. and its dependencies');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (Config::isDev()) {
            $this->writeError('Error: Only run this command in a live environment.');
            return Command::FAILURE;
        }
        $currentMode = Registry::instance()->isMaintenanceMode();

        try {
            Registry::instance()->setMaintenanceMode();

            // TODO: create a backup of the database before executing this.....

            $cmdList = [
                'git reset --hard',
                'git checkout master',
                'git pull',
                'git log --tags --simplify-by-decoration --pretty="format:%ci %d %h"',
                'git checkout {tag}',
                'composer update'
            ];

            if (Config::isDev()) {        // For testing
                array_unshift($cmdList, 'ci');
                $cmdList[] = 'git reset --hard';
                $cmdList[] = 'git checkout master';
                $cmdList[] = 'composer update';
            }

            $tag = '';
            $output = [];
            foreach ($cmdList as $i => $cmd) {
                unset($output);
                if (str_starts_with($cmd, 'git log ')) {      // find tag version
                    exec($cmd . ' 2>&1', $output, $ret);
                    foreach ($output as $line) {
                        if (preg_match('/\((tag\: )*([0-9\.]+)\)/', $line, $regs)) {
                            $tag = $regs[2];
                            break;
                        }
                    }
                    if (!$tag) {
                        throw new Exception('Error: Cannot find version tag.');
                    }
                } else {
                    if ($tag) {
                        $cmd = str_replace('{tag}', $tag, $cmd);
                    }
                    $this->writeInfo($cmd);
                    if (str_starts_with($cmd, 'composer ')) {
                        system($cmd);
                    } else {
                        exec($cmd . ' 2>&1', $output, $ret);
                        if ($cmd == 'ci') {
                            continue;
                        }
                        $this->write('  ' . implode("\n  ", $output));
                    }
                }
            }

        } catch (\Exception $e) {
            $this->writeError($e->getMessage());
            return Command::FAILURE;
        } finally {
            Registry::instance()->setMaintenanceMode($currentMode);
        }

        return Command::SUCCESS;
    }

}
