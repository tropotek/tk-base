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
class MakeEdit extends MakerIface
{

    /**
     *
     */
    protected function configure()
    {
        $this->setName('make-edit')
            ->setAliases(array('me'))
            ->setDescription('Create a PHP Controller Edit Class from the DB schema');
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
        $this->makeEdit();


//        $config = \Bs\Config::getInstance();
//        $phpFile = $config->getSitePath() . '/src/' . str_replace('\\', '/', $this->getGen()->getControllerNamespace()) . '/' . $this->getGen()->getClassName() . '/Edit.php';
//        if (!$input->getOption('overwrite'))
//            $phpFile = $this->makeUniquePhpFilename($phpFile);
//
//        $formCode = $this->getGen()->makeEdit($input->getOptions());
//
//        if (!is_dir(dirname($phpFile))) {
//            $this->writeComment('Creating Path: ' . dirname($phpFile));
//            mkdir(dirname($phpFile), 0777, true);
//        }
//
//        $this->writeComment('Writing: ' . $phpFile);
//        file_put_contents($phpFile, $formCode);

    }

}
