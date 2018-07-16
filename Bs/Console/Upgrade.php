<?php
namespace Bs\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2017 Michael Mifsud
 */
class Upgrade extends Iface
{

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
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $config = $this->getConfig();
        if ($config->isDebug()) {
            $this->writeError('Error: Only run this command in a live environment.');
            return;
        }
        
        $cmdList = array(
            'git reset --hard',
            'git checkout master',
            'git pull',
            'git log --tags --simplify-by-decoration --pretty="format:%ci %d %h"',
            'git checkout {tag}',
            'composer update'
        );

        if ($config->isDebug()) {
            array_unshift($cmdList, 'ci');
            $cmdList[] = 'git reset --hard';
            $cmdList[] = 'git checkout master';
            $cmdList[] = 'composer update';
        }


        $tag = '';
        $output = array();
        foreach ($cmdList as $i => $cmd) {
            unset($output);
            if (preg_match('/^git log /', $cmd)) {      // find tag version
                exec($cmd . ' 2>&1', $output, $ret);
                foreach ($output as $line) {
                    if (preg_match('/\(tag\: ([0-9\.]+)\)/', $line, $regs)) {
                        $tag = $regs[1];
                        break;
                    }
                }
/*
lms@252s-weblive:~/public_html/app/voce2$ ./bin/cmd ug
upgrade
git reset --hard
  HEAD is now at db58441 Preparing branch master for new release
git checkout master
  Previous HEAD position was db58441... Preparing branch master for new release
  Switched to branch 'master'
git pull
  From github.com:fvas-elearning/lti-voce
     b2cb233..573c625  master     -> origin/master
  From github.com:fvas-elearning/lti-voce
   * [new tag]         2.2.8      -> 2.2.8
  Updating b2cb233..573c625
  Fast-forward
   changelog.md                    |    5 +++++
   src/config/sql/mysql/000008.sql |    4 ++--
   2 files changed, 7 insertions(+), 2 deletions(-)
Error: Cannot find version tag.                                         <======== Why!!!!
lms@252s-weblive:~/public_html/app/voce2$
*/




                if (!$tag) {
                    $this->writeError('Error: Cannot find version tag.');
                    return;
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

    }

}
