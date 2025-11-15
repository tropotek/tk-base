<?php
namespace Bs\Composer;

use Bs\Factory;
use Composer\IO\IOInterface;
use Composer\Script\Event;
use Bs\Db\SqlMigrate;
use Tk\Exception;
use Tk\Path;
use Tk\Db;

/**
 * A Composer installer class for the Tk framework
 *
 * Add the following to your top-level composer.json:
 * "scripts": {
 *   "post-install-cmd": [
 *     "Bs\\Composer\\Installer::postInstall"
 *   ],
 *   "post-update-cmd": [
 *     "Bs\\Composer\\Installer::postUpdate"
 *   ]
 * }
 *
 * Notes:
 *    - Use `composer install` to install a production site
 *    - Use `composer update` to update a development sites libs
 *    - Use `composer update --no-scripts` to skip the post-install/update scripts
 *    - Use `composer dump-autoload` to update the autoloader class map
 *
 */
class Installer
{
    protected static mixed $_instance = null;

    protected bool $isInstall = false;


    /**
     * Called by Composer when the post-installation event is executed
     */
    public static function postInstall(Event $event): void
    {
        self::instance()->execute($event, true);
    }

    /**
     * Called by Composer when the post-update event is executed
     */
    public static function postUpdate(Event $event): void
    {
        self::instance()->execute($event);
    }


    public static function instance(): self
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Set-up a site's config.php, .htaccess, paths, and database tables.
     */
    protected function execute(Event $event, bool $isInstall = false): void
    {
        $io = $event->getIO();

        try {
            $this->isInstall = $isInstall;
            $sitePath = $_SERVER['PWD'];
            $configVars = [];

            $configInFile = $sitePath . '/config.php.in';
            $configFile = $sitePath . '/config.php';
            $htInFile = $sitePath . '/.htaccess.in';
            $htFile = $sitePath . '/.htaccess';

            $hasConfig = is_file($configFile);
            
            // Get the PHP user that will be executing the scripts
            $phpUser = $this->getSystemUser();

            $this->writePackageInfo($event);

            if ($hasConfig) {
                include_once $sitePath.'/_prepend.php';
                $config = \Tk\Config::instance();
            }

            // Create a new config.php
            if (is_file($configInFile)) {
                $this->createConfigFile($event, $configInFile, $configFile);
            }

            // Create .htaccess
            if (is_file($htInFile)) {
                $configVars['base.url'] = $this->createHtaccesFile($event, $htInFile, $htFile, ['sitePath' => $sitePath]);
            }

            // Bootstrap the system
            include_once $sitePath.'/_prepend.php';
            $config = \Tk\Config::instance();

            Db::connect($config->get('db.mysql', ''));

            // Create the `/data` path if not exists
            $dataPath = Path::createDataPath();
            if (!is_dir($dataPath)) {
                $io->write($this->green('Creating data directory: ' . $dataPath));
                mkdir($dataPath, 0777, true);
            }

            // -----------------  DM Migration START  -----------------

            $drop = false;
            $tables = Db::getTableList();
            if (count($tables) && !$this->isInstall) {
                $drop = $io->askConfirmation($this->warning('Replace the existing database. WARNING: Existing data tables will be deleted! [N]: '), false);
            }
            if ($drop) {
                $exclude = [Db\MySqlSession::$DB_TABLE];
                Db::dropAllTables(true, $exclude);
            }

            $this->siteDbMigration($event);

            if ($this->isInstall) {
                $io->write("Check the config file before releasing: {$config['base.path']}/config.php");
                $io->write('Visit Site: ' . \Tk\Uri::create($configVars['base.url'] ?? '')->toString());
            }
        } catch (\Exception $e) {
            $io->write($this->red('Error: ' . $e->getMessage() . ' (see php_log)'));
        }
    }

    private function createConfigFile(Event $event, string $configInFile, string $configFile): void
    {
        $io = $event->getIO();

        if (is_file($configFile)) {
            if ($this->isInstall) return;
            $overwrite = $io->askConfirmation($this->warning('Do you want to replace the existing site configuration [N]: '), false);
            if (!$overwrite) return;
        }

        if (!is_file($configFile)) {
            $configContents = strval(file_get_contents($configInFile));
            $io->write($this->green('Please answer the following questions to setup your new site configuration.'));
            $configVars = $this->userDbInput($io);
            $configVars['system.encrypt'] = hash('sha256', 'Tropotek_'.microtime());

            // update the config contents string
            foreach ($configVars as $k => $v) {
                $configContents = str_replace("{{$k}}", $v, $configContents);
            }

            $io->write($this->green('Saving config.php'));
            file_put_contents($configFile, $configContents);
        }
    }

    private function createHtaccesFile(Event $event, string $htInFile, string $htFile, array $params): string
    {
        $io = $event->getIO();

        if (is_file($htFile)) {
            if ($this->isInstall) return '/';
            $overwrite = $io->askConfirmation($this->warning('Do you want to replace the existing .htaccess file [N]: '), false);
            if (!$overwrite) return '/';
        }

        $io->write($this->green('Creating .htaccess file'));
        copy($htInFile, $htFile);
        $baseurl = '/';
        if (preg_match('/(.+)\/public_html\/(.*)/', $params['sitePath'] ?? '', $regs)) {
            $baseurl = '/' . $regs[2] . '/';
        }
        $baseurl = trim($io->ask($this->bold('What is the base URL path [' . $baseurl . ']: '), $baseurl));
        if (!$baseurl) $baseurl = '/';
        $io->write($this->green('Saving .htaccess file'));
        $buf = strval(file_get_contents($htFile));
        $buf = str_replace('RewriteBase /', 'RewriteBase ' . $baseurl, $buf);
        file_put_contents($htFile, $buf);

        return $baseurl;
    }

    private function siteDbMigration(Event $event): void
    {
        $io = $event->getIO();

        // Update Database tables
        $tables = Db::getTableList();
        if (count($tables)) {
            $io->write($this->green('Database Upgrade:'));
        } else {
            $io->write($this->green('Database Install:'));
        }

        // migrate site sql files
        if (!SqlMigrate::migrateAll([$io, 'write'])) {
            throw new Exception("Failed to migrate files");
        }

        $io->write($this->green('Purging caches'));

        if (class_exists(Factory::class)) {
            Factory::instance()->purgeCache();
            Factory::instance()->getCompiledRoutes(true);
        }

        $io->write($this->green('Database Migration Complete'));
    }

    protected function userDbInput(IOInterface $io): array
    {
        $config = [];
        // Prompt for the database access
        $i = 0;
        $dbTypes = ['mysql'];
        if (count($dbTypes) > 1) {
            $io->write('<options=bold>');
            $i = $io->select('Select the DB type [mysql]: ', $dbTypes, '0');
        }
        $io->write('</>');
        $config['db.default.type'] = $dbTypes[$i];
        $config['db.default.host'] = $io->ask($this->bold('Set the DB hostname [localhost]: '), 'localhost');
        $config['db.default.port'] = $io->ask($this->bold('Set the DB port [3306]: '), '3306');
        $config['db.default.name'] = $io->askAndValidate($this->bold('Set the DB name: '), function ($data) { if (!$data) throw new \Exception('Please enter the DB name to use.');  return $data; });
        $config['db.default.user'] = $io->askAndValidate($this->bold('Set the DB user: '), function ($data) { if (!$data) throw new \Exception('Please enter the DB username.'); return $data; });
        $config['db.default.pass'] = $io->askAndValidate($this->bold('Set the DB password: '), function ($data) { if (!$data) throw new \Exception('Please enter the DB password.'); return $data; });

        $config['db.mysql'] = sprintf('%s:%s/%s/%s/%s',
            $config['db.default.host'],
            $config['db.default.port'],
            $config['db.default.user'],
            $config['db.default.pass'],
            $config['db.default.name'],
        );

        return $config;
    }

    protected function getSystemUser(): string
    {
        if (function_exists('posix_getpwuid')) {
            $a = posix_getpwuid(intval(fileowner(__FILE__)));
            if (is_array($a)) {
                return $a['dir'];
            }
        }
        return `whoami`;
    }

    protected function writePackageInfo(Event $event): void
    {
        $io = $event->getIO();
        $composer = $event->getComposer();
        $pkg = $composer->getPackage();

        $name = substr($pkg->getName(), strrpos($pkg->getName(), '/')+1);
        $version = $pkg->getFullPrettyVersion();
        $releaseDate = $pkg->getReleaseDate()->format('Y-m-d H:i:s');
        $year = $pkg->getReleaseDate()->format('Y');
        $desc = wordwrap($pkg->getDescription(), 45, "\n               ");
        $authors = [];
        foreach ($pkg->getAuthors() as $auth) {
            $authors[] = $auth['name'] ?? '';
        }
        $authors = implode(', ', $authors);

        $head = <<<STR
        -----------------------------------------------------------
               $name - (c) tropotek.com $year
        -----------------------------------------------------------
          Project:     $name
          Version:     $version
          Released:    $releaseDate
          Author:      $authors
          Description: $desc
        -----------------------------------------------------------
        STR;
        $io->write($this->bold($head));

    }

    protected function bold(string $str): string { return '<options=bold>'.$str.'</>'; }

    protected function green(string $str): string { return '<fg=green>'.$str.'</>'; }

    protected function warning(string $str): string { return '<fg=yellow;options=bold>'.$str.'</>'; }

    protected function red(string $str): string { return '<fg=white;bg=red>'.$str.'</>'; }

    protected function quote(string $str): string { return '\''.$str.'\''; }

    protected function vd(mixed $obj): void
    {
        echo print_r($obj, true) . "\n";
    }
}
