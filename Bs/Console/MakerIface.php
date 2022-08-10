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
class MakerIface extends Iface
{
    /**
     * @var \Bs\Util\ModelGenerator
     */
    protected $gen = null;

    /**
     * @var string
     */
    protected $basePath = '';

    /**
     *
     */
    protected function configure()
    {
        $this->addArgument('table', InputArgument::REQUIRED, 'The name of the table to generate the class file from.')
            ->addOption('overwrite', 'o', InputOption::VALUE_NONE, 'Overwrite existing class files.')
            ->addOption('modelForm', 'm', InputOption::VALUE_NONE, 'Generate a ModelForm object instead')       // This object is deprecated
            ->addOption('namespace', 'N', InputOption::VALUE_OPTIONAL, 'A custom namespace (Default: App)', '')
            ->addOption('classname', 'C', InputOption::VALUE_OPTIONAL, 'A custom Classname (Default: `TableName`)', '')
            ->addOption('basepath', 'B', InputOption::VALUE_OPTIONAL, 'A base src path to save the file (Default: {sitePath}/src)', '');
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
        if (!$this->getConfig()->isDebug()) {
            throw new \Exception('Error: Only run this command in a debug environment.');
        }

        $this->basePath = $input->getOption('basepath');
        if (!$this->getBasePath())
            $this->basePath = $this->getConfig()->getSitePath() . '/src';

        $this->gen = \Bs\Util\ModelGenerator::create($this->getConfig()->getDb(),
            $input->getArgument('table'),
            $input->getOption('namespace'),
            $input->getOption('classname')
        );
    }

    /**
     * @throws \Exception
     */
    protected function makeAll()
    {
        $this->makeModels();
        $this->makeTable();
        $this->makeForm();
        $this->makeManager();
        $this->makeEdit();
    }

    /**
     * @throws \Exception
     */
    protected function makeModels()
    {
        $file = $this->getBasePath() . '/' . str_replace('\\', '/', $this->getGen()->getDbNamespace()) . '/' . $this->getGen()->getClassName() . '.php';
        $code = $this->getGen()->makeModel($this->getInput()->getOptions());
        $file = $this->writeFile($file, $code);
        $this->writeComment('Writing Model: ' . $file);

        $file = $this->getBasePath() . '/' . str_replace('\\', '/', $this->getGen()->getDbNamespace()) . '/' . $this->getGen()->getClassName() . 'Map.php';
        $code = $this->getGen()->makeMapper($this->getInput()->getOptions());
        $file = $this->writeFile($file, $code);
        $this->writeComment('Writing Mapper: ' . $file);
    }

    /**
     * @throws \Exception
     */
    protected function makeForm()
    {
        $file = $this->getBasePath() . '/' . str_replace('\\', '/', $this->getGen()->getFormNamespace()) . '/' . $this->getGen()->getClassName() . '.php';
        $code = $this->getGen()->makeForm($this->getInput()->getOptions());
        $file = $this->writeFile($file, $code);
        $this->writeComment('Writing Form: ' . $file);
    }

    /**
     * @throws \Exception
     */
    protected function makeEdit()
    {
        $file = $this->getBasePath() . '/' . str_replace('\\', '/', $this->getGen()->getControllerNamespace()) . '/' . $this->getGen()->getClassName() . '/Edit.php';
        $code = $this->getGen()->makeEdit($this->getInput()->getOptions());
        $file = $this->writeFile($file, $code);
        $this->writeComment('Writing Edit Form: ' . $file);
    }

    /**
     * @throws \Exception
     */
    protected function makeTable()
    {
        $file = $this->getBasePath() . '/' . str_replace('\\', '/', $this->getGen()->getTableNamespace()) . '/' . $this->getGen()->getClassName() . '.php';
        $code = $this->getGen()->makeTable($this->getInput()->getOptions());
        $file = $this->writeFile($file, $code);
        $this->writeComment('Writing Table: ' . $file);
    }

    /**
     * @throws \Exception
     */
    protected function makeManager()
    {
        $file = $this->getBasePath() . '/' . str_replace('\\', '/', $this->getGen()->getControllerNamespace()) . '/' . $this->getGen()->getClassName() . '/Manager.php';
        $code = $this->getGen()->makeManager($this->getInput()->getOptions());
        $file = $this->writeFile($file, $code);
        $this->writeComment('Writing Manager Form: ' . $file);
    }



    /**
     * @param string $file
     * @param string $code
     * @return string
     */
    protected function writeFile($file, $code)
    {
        if (!$this->getInput()->getOption('overwrite'))
            $file = $this->makeUniquePhpFilename($file);
        if (!is_dir(dirname($file))) {
            $this->writeComment('Creating Path: ' . dirname($file));
            mkdir(dirname($file), 0777, true);
        }
        file_put_contents($file, $code);
        return $file;
    }

    /**
     * @return \Bs\Util\ModelGenerator
     */
    public function getGen()
    {
        return $this->gen;
    }

    /**
     * @return string
     */
    public function getBasePath()
    {
        return $this->basePath;
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
