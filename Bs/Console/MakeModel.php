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
class MakeModel extends MakerIface
{

    /**
     *
     */
    protected function configure()
    {
        $this->setName('make-model')
            ->setAliases(array('mm'))
            ->setDescription('Create a PHP Model Class from the DB schema');
        parent::configure();
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
        $this->makeModels();

//        $config = \Bs\Config::getInstance();
//        $modelPath = $config->getSitePath() . '/src/' . str_replace('\\', '/', $this->getGen()->getDbNamespace()) . '/' . $this->getGen()->getClassName() . '.php';
//        if (!$input->getOption('overwrite'))
//            $modelPath = $this->makeUniquePhpFilename($modelPath);
//        $modelContent = $this->getGen()->makeModel();
//
//        $mapPath = $config->getSitePath() . '/src/' . str_replace('\\', '/', $this->getGen()->getDbNamespace()) . '/' . $this->getGen()->getClassName() . 'Map.php';
//        if (!$input->getOption('overwrite'))
//            $mapPath = $this->makeUniquePhpFilename($mapPath);
//        $mapContent = $this->getGen()->makeMapper();
//
//        if (!is_dir(dirname($modelPath))) {
//            $this->writeComment('Creating Db path: ' . dirname($modelPath));
//            mkdir(dirname($modelPath), 0777, true);
//        }
//
//        $this->writeComment('Writing Model: ' . $modelPath);
//        file_put_contents($modelPath, $modelContent);
//
//        $this->writeComment('Writing Mapper: ' . $mapPath);
//        file_put_contents($mapPath, $mapContent);

    }

}
