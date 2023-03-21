<?php
namespace Bs\Console;

use Bs\Uri;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * @author Michael Mifsud <http://www.tropotek.com/>
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
            ->setAliases(array('mi'))
            ->addOption('no-cache', 'C', InputOption::VALUE_NONE, 'Force downloading of the live DB. (Cached for the day)')
            ->addOption('no-sql', 'S', InputOption::VALUE_NONE, 'Do not execute the sql component of the mirror')
            ->addOption('no-dev', 'f', InputOption::VALUE_NONE, 'Do not execute the dev sql file')
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

        $config = $this->getConfig();
        if (!$config->isDebug()) {
            $this->writeError('Only run this command in a debug environment.');
            return;
        }
        if (!$this->getConfig()->get('db.skey')) {
            $this->writeError('Secret key not valid: ' . $this->getConfig()->get('db.skey'));
            return;
        }

        $backupSqlFile = $config->getTempPath() . '/tmpt.sql';
        $mirrorFileGz  = $config->getTempPath() . '/'.\Tk\Date::create()->format(\Tk\Date::FORMAT_ISO_DATE).'-tmpl.sql.gz';
        $mirrorFileSQL = $config->getTempPath() . '/'.\Tk\Date::create()->format(\Tk\Date::FORMAT_ISO_DATE).'-tmpl.sql';

        // Delete live cached files
        $list = glob($config->getTempPath().'/*-tmpl.sql');
        foreach ($list as $file) {
            if ($input->getOption('no-cache') || $file != $mirrorFileSQL) {
                if (is_file($file)) unlink($file);
            }
        }
        // Remove any prev local backup files
        if (is_file($backupSqlFile)) unlink($backupSqlFile);

        $db = $config->getDb();
        $dbBackup = \Tk\Util\SqlBackup::create($db);
        $exclude = [\Tk\Session\Adapter\Database::$DB_TABLE];

        if (!$input->getOption('no-sql')) {
            if (!is_file($mirrorFileSQL) || $input->getOption('no-cache')) {
                if (!$this->getConfig()->get('mirror.db')) {
                    $this->writeError('Invalid DB source mirror URL: ' . $this->getConfig()->get('mirror.db'));
                    return;
                }
                $this->writeComment('Download fresh mirror file: ' . $mirrorFileGz);
                // get a copy of the remote DB to be mirrored
                if (is_file($mirrorFileGz)) unlink($mirrorFileGz);
                $this->postRequest($this->getConfig()->get('mirror.db'), $mirrorFileGz);

            } else {
                $this->writeComment('Using existing mirror file: ' . $mirrorFileGz);
            }

            // Prevent accidental writing to live DB
            $this->writeComment('Backup this DB to file: ' . $backupSqlFile);
            $dbBackup->save($backupSqlFile, ['exclude' => $exclude]);

            $this->write('Drop this DB tables');
            $db->dropAllTables(true, $exclude);

            // Uncompress file first
            if (is_file($mirrorFileGz)) {
                $command = sprintf('gunzip %s', escapeshellarg($mirrorFileGz));
                exec($command, $out, $ret);
                if ($ret != 0) {
                    throw new \Tk\Db\Exception(implode("\n", $out));
                }
            }

            $this->write('Import mirror file to this DB');
            $dbBackup->restore($mirrorFileSQL);

            // TODO: This should be run in the migration command only
            // Run all static scripts views.sql, triggers.sql, procedures.sql, functions.sql
//            $staticFiles = ['views.sql', 'triggers.sql', 'procedures.sql', 'functions.sql'];
//            foreach ($staticFiles as $file) {
//                $path = "{$config->getSitePath()}/src/config/sql/{$file}";
//                if (is_file($path)) {
//                    $this->writeGreen('Applying ' . $file);
//                    $dbBackup->restore($path);
//                }
//            }

            if (!$input->getOption('no-dev')) {
                $debugSqlFile = $config->getSitePath() . '/bin/assets/debug.sql';
                if ($config->isDebug() && is_file($debugSqlFile)) {
                    $this->writeBlue('Apply dev sql updates');
                    $dbBackup->restore($debugSqlFile);
                }
            }
            //if (is_file($backupSqlFile)) unlink($backupSqlFile);
        }

        // if with Data, copy the data folder and its files
        if ($input->getOption('copy-data')) {

            if (!$this->getConfig()->get('mirror.data')) {
                $this->writeError('Invalid data source mirror URL: ' . $this->getConfig()->get('mirror.data'));
                return;
            }

            $dataPath = $config->getDataPath();
            $dataBakPath = $dataPath . '_bak';
            $tempDataFile  = $config->getSitePath() . '/dest-'.\Tk\Date::create()->format(\Tk\Date::FORMAT_ISO_DATE).'-data.tgz';

            $this->write('Downloading live data files...[Please wait]');
            if (is_file($tempDataFile)) unlink($tempDataFile);
            $this->postRequest($this->getConfig()->get('mirror.data'), $tempDataFile);
            $this->write('Download Complete!');

            if (is_dir($dataBakPath)) { // Remove any old bak data folder
                $this->write('Deleting existing data backup: ' . $dataBakPath);
                $cmd = sprintf('rm -rf %s ', escapeshellarg($dataBakPath));
                system($cmd);
            }
            if (is_dir($dataPath)) {    // Move existing data to data_bak
                $this->write('Move current data files to backup location: ' . $dataBakPath);
                $cmd = sprintf('mv %s %s ', escapeshellarg($dataPath), escapeshellarg($dataBakPath));
                //$this->write($cmd);
                system($cmd);
            }
            if (is_dir($dataPath)) {    // Move temp data to data
                $this->write('Extract downloaded data files to: ' . $dataPath);
                $cmd = sprintf('cd %s && tar zxf %s ', escapeshellarg($config->getSitePath()), escapeshellarg(basename($tempDataFile)));
                //$this->write($cmd);
                system($cmd);
            }

            //
//            $this->write('Change data folder permissions');
//            if (is_dir($dataPath)) {
//                $cmd = sprintf('chmod ug+rw %s -R', escapeshellarg($dataPath));
//                //$this->write($cmd);
//                system($cmd);
//                $cmd = sprintf('chgrp www-data %s -R', escapeshellarg($dataPath));
//                //$this->write($cmd);
//                system($cmd);
//            }
            if (is_file($tempDataFile)) unlink($tempDataFile);
        }

        $this->write('Complete!!!');
    }

    protected function postRequest($srcUrl, $destPath)
    {
        $query = 'db_skey=' . $this->getConfig()->get('db.skey');
        $fp = fopen($destPath, 'w');
        $curl = curl_init(Uri::create($srcUrl)->setScheme(Uri::SCHEME_HTTP_SSL)->toString());
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $query);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_FILE, $fp);

        curl_exec($curl);
        if(curl_error($curl)) {
            fwrite($fp, curl_error($curl));
        }
        curl_close($curl);
        fclose($fp);
    }

}
