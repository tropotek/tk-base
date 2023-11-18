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
class Upgrade extends Iface
{

    private $orgMaint = false;

    /**
     *
     */
    protected function configure()
    {
        $this->setName('upgrade')
            ->setAliases(array('ug'))
            ->setDescription('Call this to upgrade the site from git and update its dependencies');
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

        $config = $this->getConfig();
        if ($config->isDebug()) {
            $this->writeError('Error: Only run this command in a live environment.');
            return 1;
        }


        // TODO: create a backup of the database before executing this.....

        $this->orgMaint = (bool)$config->get('site.maintenance.enabled');


        \Bs\Listener\MaintenanceHandler::enableMaintenanceMode(true);

        $cmdList = array(
            'git reset --hard',
            'git checkout master',
            'git pull',
            'git log --tags --simplify-by-decoration --pretty="format:%ci %d %h"',
            'git checkout {tag}',
            'composer update --ignore-platform-reqs'
        );

        if ($config->isDebug()) {
            array_unshift($cmdList, 'ci');
            $cmdList[] = 'git reset --hard';
            $cmdList[] = 'git checkout master';
            $cmdList[] = 'composer update --ignore-platform-reqs';
        }


        $tag = '';
        $output = array();
        foreach ($cmdList as $i => $cmd) {
            unset($output);
            if (preg_match('/^git log /', $cmd)) {      // find tag version
                exec($cmd . ' 2>&1', $output, $ret);
                foreach ($output as $line) {
                    if (preg_match('/\((tag\: )*([0-9\.]+)\)/', $line, $regs)) {
                        $tag = $regs[2];
                        break;
                    }
                }
                if (!$tag) {
                    $this->writeError('Error: Cannot find version tag.');
                    return 1;
                }
            } else {
                if ($tag) {
                    $cmd = str_replace('{tag}', $tag, $cmd);
                }
                $this->writeInfo($cmd);
                if (preg_match('/^composer /', $cmd)) {
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
        return 0;

    }

    public function run(InputInterface $input, OutputInterface $output)
    {
        try {
            $r = parent::run($input, $output);
        } finally {
            \Bs\Listener\MaintenanceHandler::enableMaintenanceMode($this->orgMaint);
        }
        return $r;
    }

}
