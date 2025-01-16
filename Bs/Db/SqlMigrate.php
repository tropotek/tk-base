<?php
namespace Bs\Db;

use Tk\Config;
use Tk\FileUtil;
use Tk\Log;
use Tk\Db;

/**
 * DB migration tool
 *
 * It is a good idea to start with a number to ensure that the files are
 * executed in the required order. Files found will be sorted alphabetically.
 *
 * <code>
 *   SqlMigrate::instance()->migrateList([]);
 * </code>
 *
 * Migration files can be of type .sql or .php.
 * The php files are called with the include() command
 * and the php file should return a closure like the following:
 * <code>
 *  return function (Tk\Db\Pdo $db) {
 *      ...
 *  };
 * </code>
 */
class SqlMigrate
{
    public    static string $TABLE = '_migrate';

    protected static mixed $_instance = null;

    protected string $backupFile = '';


    /**
     * Gets an instance of this object, if none exists one is created
     */
    public static function instance(): self
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __destruct()
    {
        $this->deleteBackup();
    }

    /**
     * execute new site/lib sql files that have not been migrated yet
     */
    public static function migrateSite(?callable $log = null) :bool
    {
        // find default migration paths
        $vendorPath   = Config::makePath(Config::instance()->get('path.vendor.org'));
        $migratePaths = [];
        $libPaths     = scandir($vendorPath);

        if (is_array($libPaths)) {
            array_shift($libPaths);
            array_shift($libPaths);
            $migratePaths = array_map(fn($path) => $vendorPath . '/' . $path . '/config/sql', $libPaths);
            array_unshift($migratePaths, Config::makePath('/src/config/sql'));
        } else {
            Log::warning("Vendor path not found: $vendorPath");
        }

        // migrate found files
        return self::instance()->migrateList($migratePaths, $log);
    }

    /**
     * execute static sql file listed in the config setting 'db.migrate.static'
     */
    public static function migrateStatic(?callable $log = null) :bool
    {
        $config = Config::instance();

        foreach ($config->get('db.migrate.static') as $file) {
            $path = Config::makePath($file);
            if (is_file($path)) {
                // write to log file
                if (is_callable($log)) call_user_func_array($log, ['Applying ' . $file]);
                $options = Db::parseDsn($config->get('db.mysql'));
                Db\DbBackup::restore($path, $options);
            }
        }

        return true;
    }

    /**
     * Execute the dev php file to allow configuration of a dev environment.
     * Cannot be executed in a production environment.
     */
    public static function migrateDev(?callable $log = null) :bool
    {
        if (!Config::isDev()) {
            return false;
        }
        $devFile = Config::makePath(Config::instance()->get('debug.script'));
        if (is_file($devFile)) {
            if (is_callable($log)) call_user_func_array($log, ['Setup dev environment: ' . Config::instance()->get('debug.script')]);
            include($devFile);
        }
        return true;
    }

    /**
     * Call this with a list of paths to search for migration files and execute each migration
     * in order they are supplied in the array
     * Returns an array of processed migrate files
     */
    public function migrateList(array $migrateList, ?callable $log = null): bool
    {
        $this->install();
        $list = $this->search($migrateList);
        foreach ($list as $k => $path) {
            if (is_file($path)) {
                if (!$this->migrateFile($path, $log)) {
                    // todo Should revert the DB at this stage...

                    if (is_callable($log)) call_user_func_array($log, ["Failed to execute $path"]);
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Execute a migration class or sql script...
     * the file is then added to the db and cannot be executed again.
     * Ignore any files starting with an underscore '_'
     */
    public function migrateFile(string $file, ?callable $log = null): bool
    {
        try {
            $this->install();

            $file = Config::makePath($this->toRelative($file));
            if (str_starts_with(basename($file), '_')) return false;
            if (!is_readable($file)) return false;
            if ($this->hasPath($this->toRelative($file))) return false;

            if (!$this->backupFile) {   // only run once per session.
                $options = Db::parseDsn(Config::instance()->get('db.mysql'));
                $this->backupFile = Db\DbBackup::save(Config::makePath(Config::getTempPath()), $options);
            }

            if (is_callable($log)) call_user_func_array($log, ['Migrating ' . $file]);
            if (preg_match('/\.php$/i', basename($file))) {  // Include .php files
                $callback = include $file;
                if (is_callable($callback)) {
                    $callback();
                }
                $this->insertPath($file);
            } else {  // is sql
                // replace any table prefix
                $sql = strval(file_get_contents($file));
                if (!strlen(trim($sql))) return false;

                $stm = Db::getPdo()->prepare($sql);
                $stm->execute();
                $stm->closeCursor();

                $this->insertPath($file);
            }
        } catch (\Exception $e){
            //vd($sql);
            Log::error($e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * Search for all migrate files in the pathList array of files/paths
     * Return a sorted flattened array that has the files that can be executed
     */
    protected function search(array $pathList): array
    {
        $found = [];
        foreach ($pathList as $path) {
            if (is_file($path) && preg_match('/.+\/([0-9]+)\.(php|sql)$/', $path, $regs)) {
                $found[$regs[1]] = $path;
            } else if (is_dir($path)) {
                $directory = new \RecursiveDirectoryIterator($path);
                $it = new \RecursiveIteratorIterator($directory);
                $regex = new \RegexIterator($it, '/.+\/([0-9]+)\.(php|sql)$/', \RegexIterator::GET_MATCH);
                foreach ($regex as $file) {
                    $found[$file[1] ?? '000000'] = $file[0];
                }
            }
        }

        ksort($found);
        return $found;
    }

    protected function restoreBackup(bool $deleteFile = true): void
    {
        if ($this->backupFile) {
            $options = Db::parseDsn(Config::instance()->get('db.mysql'));
            Db\DbBackup::restore($this->backupFile, $options);
            if ($deleteFile) {
                $this->deleteBackup();
            }
        }
    }

    protected function deleteBackup(): void
    {
        if (is_writable($this->backupFile)) {
            unlink($this->backupFile);
            $this->backupFile = '';
        }
    }

    /**
     * install the migration table to cache executed scripts
     */
    protected function install(): void
    {
        if (Db::tableExists($this->getTable())) return;
        $tbl = $this->getTable();
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `$tbl` (
  path VARCHAR(128) NOT NULL DEFAULT '',
  rev VARCHAR(16) NOT NULL DEFAULT '',
  created TIMESTAMP,
  PRIMARY KEY (path)
);
SQL;
        Db::execute($sql);
    }

    /**
     * Return true if the migration table is empty or does not exist
     */
    protected function isInstall(): bool
    {
        if (!Db::tableExists($this->getTable())) return true;
        $sql = "SELECT * FROM `{$this->getTable()}` LIMIT 1";
        $res = Db::getPdo()->query($sql);
        if ($res === false) return false;
        if (!$res->rowCount()) return true;
        return false;
    }

    protected function hasPath(string $path): bool
    {
        $stm = Db::getPdo()->prepare("SELECT * FROM `{$this->getTable()}` WHERE path = :path LIMIT 1");
        $stm->execute(compact('path'));
        return $stm->rowCount() > 0;
    }

    protected function insertPath(string $path): int
    {
        Log::info("Migrating file: {$this->toRelative($path)}");
        $path = $this->toRelative($path);
        $rev = $this->toRev($path);
        $stm = Db::getPdo()->prepare("INSERT INTO `{$this->getTable()}` (path, rev, created) VALUES (:path, :rev, NOW())");
        $stm->execute(compact('path', 'rev'));
        return $stm->rowCount();
    }

    protected function deletePath(string $path): int
    {
        $path = $this->toRelative($path);
        $stm = Db::getPdo()->prepare("DELETE FROM `{$this->getTable()}` WHERE path = :path LIMIT 1");
        $stm->execute(compact('path'));
        return $stm->rowCount();
    }

    private function toRelative(string $path): string
    {
        return rtrim(str_replace(Config::getBasePath(), '', $path), '/');
    }

    /**
     * Return the revision string part of the path
     */
    private function toRev(string $path): string
    {
        $path = basename($path);
        return FileUtil::removeExtension($path);
    }

    protected function getTable(): string
    {
        return self::$TABLE;
    }
}