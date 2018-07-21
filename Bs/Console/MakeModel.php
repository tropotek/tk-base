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
class MakeModel extends Iface
{

    /**
     *
     */
    protected function configure()
    {
        $this->setName('make-model')
            ->addArgument('table', InputArgument::REQUIRED, 'The name of the table to build a class file from.')
            ->addOption('overwrite', 'o', InputOption::VALUE_OPTIONAL, 'Overwrite existing class files.', false)
            ->setAliases(array('mm'))
            ->setDescription('Create a PHP Model Class from a DB schema');
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
        $modelPath = $config->getSitePath() . '/src/' . str_replace('\\', '/', $gen->getNamespace()) . '/' . $gen->getClassName() . '.php';
        if (!$input->getOption('overwrite'))
            $modelPath = $this->makeUniquePhpFilename($modelPath);
        $modelContent = $gen->makeModel();

        $mapPath = $config->getSitePath() . '/src/' . str_replace('\\', '/', $gen->getNamespace()) . '/' . $gen->getClassName() . 'Map.php';
        if (!$input->getOption('overwrite'))
            $mapPath = $this->makeUniquePhpFilename($mapPath);
        $mapContent = $gen->makeMapper();

        if (!is_dir(dirname($modelPath))) {
            $this->writeComment('Creating Db path: ' . dirname($modelPath));
            mkdir(dirname($modelPath), 0777, true);
        }

        $this->writeComment('Writing Model: ' . $modelPath);
        file_put_contents($modelPath, $modelContent);

        $this->writeComment('Writing Mapper: ' . $mapPath);
        file_put_contents($mapPath, $mapContent);


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
