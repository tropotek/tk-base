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
class MakeForm extends Iface
{

    /**
     *
     */
    protected function configure()
    {
        $this->setName('make-form')
            ->addArgument('table', InputArgument::REQUIRED, 'The name of the table to generate the class file from.')
            ->addOption('overwrite', 'o', InputOption::VALUE_NONE, 'Overwrite existing class files.')
            ->setAliases(array('mf'))
            ->setDescription('Create a PHP Form Edit Class from the DB schema');
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

        $gen = \Bs\Util\ModelGenerator::create($db, $table);
        $phpFile = $config->getSitePath() . '/src/App/Form/' . $gen->getClassName() . '.php';
        if (!$input->getOption('overwrite'))
            $phpFile = $this->makeUniquePhpFilename($phpFile);
        $tableContent = $gen->makeForm();

        if (!is_dir(dirname($phpFile))) {
            $this->writeComment('Creating Path: ' . dirname($phpFile));
            mkdir(dirname($phpFile), 0777, true);
        }

        $this->writeComment('Writing: ' . $phpFile);
        file_put_contents($phpFile, $tableContent);

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
