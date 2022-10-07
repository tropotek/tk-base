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
        if (!$this->getConfig()->get('mirror.db')) {
            $this->writeError('Invalid source mirror URL: ' . $this->getConfig()->get('mirror.db'));
            return;
        }

        $debugSqlFile  = $config->getSitePath() . '/bin/assets/debug.sql';
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
        if (is_file($backupSqlFile))
            unlink($backupSqlFile);

        $db = $config->getDb();
        $dbBackup = \Tk\Util\SqlBackup::create($db);
        $exclude = [\Tk\Session\Adapter\Database::$DB_TABLE];

        if (!$input->getOption('no-sql')) {
            if (!is_file($mirrorFileSQL) || $input->getOption('no-cache')) {
                $this->writeComment('Download fresh mirror file: ' . $mirrorFileGz);
                // get a copy of the remote DB to be mirrored
                $query = 'db_skey=' . $this->getConfig()->get('db.skey');
                if (is_file($mirrorFileGz)) unlink($mirrorFileGz);
                $fp = fopen($mirrorFileGz, 'w');
                $curl = curl_init(Uri::create($this->getConfig()->get('mirror.db'))->setScheme(Uri::SCHEME_HTTP_SSL)->toString());
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

            $this->write('Apply dev sql updates');
            $dbBackup->restore($debugSqlFile);

            //unlink($backupSqlFile);

        }

        // if withData, copy the data folder and its files
        if ($input->getOption('copy-data')) {

            $this->writeError('Copying the data folder is disabled until further notice.');
            return;

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
