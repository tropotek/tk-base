<?php
namespace Bs\Console;

use Bs\Db\SqlMigrate;
use Bs\Factory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tk\Config;
use Tk\Db;
use Tk\Exception;
use Tk\Path;
use Tk\System;

class AppKey extends Console
{
    protected string $error = '';

    protected function configure(): void
    {
        $this->setName('app-key')
            ->setAliases(['apk'])
            ->addOption("force", "f", null, "Force the creation of a new encryption key")
            ->setDescription('Create an encryption key for the site.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!self::makeEnvFile($output, $input->getOption('force'))) {
            $this->writeError("Error writing the .env file");
            return  Command::FAILURE;
        }

        $this->write('App key updated!!!');
        return  Command::SUCCESS;
    }

    public static function makeEnvFile(OutputInterface $output, bool $force = false): bool
    {
        $basePath = Config::getBasePath();
        $envFile = $basePath . '/.env';
        $envFileSrc = $basePath . '/.env.example';

        if (!file_exists($envFile) || !is_readable($envFile)) {
            $output->writeln("Cannot read the .env file. Please check permissions.");
            return Command::FAILURE;
        }

        // copy .env file if missing
        if (!is_file($envFile)) {
            copy($envFileSrc, $envFile);
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            error_log("Failed to read the .env file");
            return false;
        }
        $found = false;
        $newKey = hash('sha256', 'Tropotek_'.microtime());

        foreach ($lines as $index => $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }
            if (str_starts_with($line, 'APP_ENCRYPT=')) {
                $found = true;
                $currentValue = trim(substr($line, strlen('APP_ENCRYPT=')));
                if ($currentValue === '' || $force) {
                    $lines[$index] = 'APP_ENCRYPT=' . $newKey;
                }
                break;
            }
        }

        if (!$found) {
            $lines[] = 'APP_ENCRYPT=' . $newKey;
        }

        $result = file_put_contents($envFile, implode(PHP_EOL, $lines) . PHP_EOL);
        if ($result === false) {
            error_log("Failed to write the .env file");
            return false;
        }

        return true;
    }

}
