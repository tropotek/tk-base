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
class Mirror extends Iface
{


    /**
     *
     */
    protected function configure()
    {
        $this->setName('mirror')
            ->addOption('no-cache', 'C', InputOption::VALUE_NONE, 'Force downloading of the live DB. (Cached for the day)')
            ->addOption('no-sql', 'S', InputOption::VALUE_NONE, 'Do not execute the sql component of the mirror')
            ->addOption('copy-data', 'd', InputOption::VALUE_NONE, 'Use scp to copy the data folder from the live site.')
            ->setDescription('Mirror the data and files from the Live site');
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

        // required vars
        $config = $this->getConfig();
        if (!$config->isDebug()) {
            $this->writeError('Error: Only run this command in a debug environment.');
            return;
        }
        $thisDb = $config->getDb();
        $liveDb = null;
        if (is_array($config->get('live.db'))) {
            $liveDb = \Tk\Db\Pdo::create($config->get('live.db'));
        }
        if (!$liveDb) {
            $this->writeError('Error: No source DB connection params available.');
            return;
        }

        $debugSqlFile = $config->getSitePath() . '/bin/assets/debug.sql';
        $thisSqlFile = $config->getTempPath() . '/tmpt.sql';
        $liveSqlFile = $config->getTempPath() . '/'.\Tk\Date::create()->format(\Tk\Date::FORMAT_ISO_DATE).'-tmpl.sql';

        // Delete live cached files
        $list = glob($config->getTempPath().'/*-tmpl.sql');
        foreach ($list as $file) {
            if ($input->getOption('no-cache') || $file != $liveSqlFile) {
                unlink($file);
            }
        }

        $liveBackup = \Tk\Util\SqlBackup::create($liveDb);
        $thisBackup = \Tk\Util\SqlBackup::create($thisDb);
        $exclude = array(\Tk\Session\Adapter\Database::$DB_TABLE);


        // Copy the data from the live DB
        if (!$input->getOption('no-sql')) {
            if (!is_file($liveSqlFile) || $input->getOption('no-cache')) {
                $this->writeComment('Download live.DB: ' . basename($liveSqlFile));
                $liveBackup->save($liveSqlFile, array('exclude' => $exclude));
            } else {
                $this->writeComment('Using existing live.DB: ' . basename($liveSqlFile));
            }

            // Prevent accidental writing to live DB
            $liveBackup = null;
            $this->writeComment('Backup this.DB to file: ' . $thisSqlFile);
            $thisBackup->save($thisSqlFile, array('exclude' => $exclude));

            $this->write('Drop this.DB tables/views');
            $thisDb->dropAllTables(true, $exclude);

            $this->write('Import live.DB file to this.DB');
            $thisBackup->restore($liveSqlFile);

            $this->write('Apply dev sql updates');
            $thisBackup->restore($debugSqlFile);

            unlink($thisSqlFile);

        }

        // if withData, copy the data folder and its files
        if ($input->getOption('copy-data')) {
            if (!$config->get('live.data.path')) {
                $this->writeError('Error: Cannot copy data files as the live.data.path is not configured.');
                return;
            }

            $dataPath = $config->getDataPath();
            $tmpPath = $dataPath . '_tmp';
            $bakPath = $dataPath . '_bak';

            if (is_dir($tmpPath)) { // Delete any tmpPath if exists
                $cmd = sprintf('rm -rf %s ', escapeshellarg($tmpPath));
                system($cmd);
            }
            if (!is_dir($tmpPath))
                mkdir($tmpPath, 0777, true);

            $this->write('Copy live data files.');
            $livePath = rtrim($config->get('live.data.path'), '/') . '/*';
            $cmd = sprintf('scp -r %s %s ', escapeshellarg($livePath), escapeshellarg($tmpPath));
            $this->write($cmd);
            system($cmd);

            if (is_dir($bakPath)) { // Remove old bak data
                $cmd = sprintf('rm -rf %s ', escapeshellarg($bakPath));
                system($cmd);
            }
            if (is_dir($dataPath)) {    // Move existing data to data_bak
                $this->write('Move current data files.');
                $cmd = sprintf('mv %s %s ', escapeshellarg($dataPath), escapeshellarg($bakPath));
                $this->write($cmd);
                system($cmd);
            }
            if (is_dir($dataPath)) {    // Move temp data to data
                $this->write('Finalise new data files.');
                $cmd = sprintf('mv %s %s ', escapeshellarg($tmpPath), escapeshellarg($dataPath));
                $this->write($cmd);
                system($cmd);
            }

            // use scp to copy the data files
            $this->write('Change data folder permissions');
            if (is_dir($dataPath)) {
                $cmd = sprintf('chmod ug+rw %s -R', escapeshellarg($dataPath));
                $this->write($cmd);
                system($cmd);
                $cmd = sprintf('chgrp www-data %s -R', escapeshellarg($dataPath));
                $this->write($cmd);
                system($cmd);
            }
        }

        //$this->write('Complete!!!');

    }



}
