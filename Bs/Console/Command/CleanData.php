<?php
namespace Bs\Console\Command;

use FilesystemIterator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Bs\Console\Console;
use Tk\Db;

class CleanData extends Console
{

    protected function configure(): void
    {
        $this->setName('clean-data')
            ->setAliases(['cd'])
            ->setDescription('Clean out empty folders in the /data dir');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->deleteEmptyFolders($this->getConfig()->getDataPath());
            $this->deleteOldFiles($this->getConfig()->getTempPath(), \Tk\Date::create()->sub(new \DateInterval('P7D')));
            $this->deleteOldFiles($this->getConfig()->getCachePath(), \Tk\Date::create()->sub(new \DateInterval('P7D')));
        } catch (\Exception $e) {
            $this->writeError($e->getMessage());
            return Command::FAILURE;
        }
        return Command::SUCCESS;
    }

    protected function deleteEmptyFolders(string $path): void
    {
        // Recursively Remove any empty folders older than 1 hour
        if (is_dir($path)) {
            $path = rtrim($path, '/');
            $this->write('   - Removing Empty Folders: ' . $path);
            \Tk\FileUtil::removeEmptyFolders($path, function ($pth) {
                $date = \Tk\Date::create(filemtime($pth));
                $now = \Tk\Date::create()->sub(new \DateInterval('PT1H'));
                if ($date < $now) {
                    $this->write('     Deleting: [' . $date->format(\Tk\Date::FORMAT_ISO_DATETIME) . '] - ' . $pth);
                    return true;
                }
                return false;
            });
        }
    }

    protected function deleteOldFiles(string $path, \DateTime $validDate): void
    {
        // clean up temp files older than $validDate
        if (is_dir($path)) {
            $dir  = new \RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS);
            $files = new \RecursiveIteratorIterator($dir, \RecursiveIteratorIterator::SELF_FIRST);
            if ($files->callHasChildren())
                $this->write('   - Removing Temp Files: ' . $path);

            /** @var \SplFileInfo $file */
            foreach ($files as $file) {
                if ($file->isDir()) continue;
                $date = \Tk\Date::create(filemtime($file));
                if ($date < $validDate) {
                    $this->write('     Deleting: [' . $date->format(\Tk\Date::FORMAT_ISO_DATETIME) . '] ' . $file->__toString());
                    unlink($file);
                }
            }
        }
    }

}
