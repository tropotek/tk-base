<?php
namespace Bs\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * @author Michael Mifsud <http://www.tropotek.com/>
 * @see http://www.tropotek.com/
 * @license Copyright 2017 Michael Mifsud
 */
class Maintenance extends Iface
{

    /**
     *
     */
    protected function configure()
    {
        $config = \Bs\Config::getInstance();
        $enabled = ($config->get('site.maintenance.enabled') == 'site.maintenance.enabled');

        $this->setName('maintenance')
            ->setDescription('Enable/Disable the sites maintenance mode. Current: ' . ($enabled ? 'Enabled' : 'Disabled'));
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Tk\Db\Exception
     * @throws \Tk\Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Do you wish to enable Maintenance Mode [y/n]?', false);
        $question->setAutocompleterValues(array('yes', 'no'));
        $question->setNormalizer(function ($value) {
            if (!$value) return false;
            $v = $value[0];
            $v = strtolower($v);
            return ($v == 'y');
        });
        $b = $helper->ask($input, $output, $question);

        if ($b) {
            $this->writeInfo('Maintenance mode enabled.');
        } else {
            $this->writeInfo('Maintenance mode disabled.');
        }
        \Bs\Listener\MaintenanceHandler::enableMaintenanceMode($b);

        $this->write();
    }
}
