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
class MakerIface extends Iface
{
    /**
     * @var \Bs\Util\ModelGenerator
     */
    protected $gen = null;

    /**
     *
     */
    protected function configure()
    {
        $this->addArgument('table', InputArgument::REQUIRED, 'The name of the table to generate the class file from.')
            ->addOption('overwrite', 'o', InputOption::VALUE_NONE, 'Overwrite existing class files.')
            ->addOption('modelForm', 'm', InputOption::VALUE_NONE, 'Generate a ModelForm object instead')       // This object is deprecated
            ->addOption('namespace', 'N', InputOption::VALUE_OPTIONAL, 'A custom namespace (Default: App)', '')
            ->addOption('classname', 'C', InputOption::VALUE_OPTIONAL, 'A custom Classname (Default: `TableName`)', '');
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

        $config = \Bs\Config::getInstance();
        $db = $config->getDb();
        $table = $input->getArgument('table');
        if (!$config->isDebug()) {
            throw new \Exception('Error: Only run this command in a debug environment.');
        }
        $this->gen = \Bs\Util\ModelGenerator::create($db, $table, $input->getOption('namespace'), $input->getOption('classname'));

    }

    /**
     * @return \Bs\Util\ModelGenerator
     */
    public function getGen()
    {
        return $this->gen;
    }

    /**
     * @param string$path
     * @return string
     */
    public function makeUniquePhpFilename($path)
    {
        $i = 1;
        while (is_file($path)) {
            $path = preg_replace('/((\.[0-9]+)?\.php)$/', '.'.$i++.'.php', $path);
        };
        return $path;
    }

}
