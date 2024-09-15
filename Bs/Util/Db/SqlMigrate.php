<?php
namespace Bs\Util\Db;

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
    public static function instance(): static
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new static();
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
    public static function migrateSite(?callable $write = null) :bool
    {
        $migrateList = Config::instance()->get('db.migrate.paths', []);
        $processed = self::instance()->migrateList($migrateList);
        foreach ($processed as $file) {
            if (is_callable($write)) {
                call_user_func_array($write, ['Migrated ' . $file]);
            }
        }
        return true;
    }

    /**
     * execute static sql file listed in the config setting 'db.migrate.static'
     */
    public static function migrateStatic(?callable $write = null) :bool
    {
        $config = Config::instance();
        foreach ($config->get('db.migrate.static') as $file) {
            $path = "{$config->getBasePath()}{$file}";
            if (is_file($path)) {
                call_user_func_array($write, ['Applying ' . $file]);
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
    public static function migrateDev(?callable $write = null) :bool
    {
        $config = Config::instance();
        if (!$config->isDev()) {
            return false;
        }
        $devFile = $config->getBasePath() . $config->get('debug.script');
        if (is_file($devFile)) {
            call_user_func_array($write, ['Setup dev environment: ' . $config->get('debug.script')]);
            include($devFile);
        }
        return true;
    }

    /**
     * Call this with a list of paths to search for migration files and execute each migration
     * in order they are supplied in the array
     * Returns an array of processed migrate files
     */
    public function migrateList(array $migrateList): array
    {
        $processed = [];
        $this->install();

        $list = $this->search($migrateList);

        foreach ($list as $k => $path) {
            if (is_file($path)) {
                if ($this->migrateFile($path)) {
                    $processed[$k] = $path;
                }
            }
        }

        return $processed;
    }

    /**
     * Execute a migration class or sql script...
     * the file is then added to the db and cannot be executed again.
     * Ignore any files starting with an underscore '_'
     */
    public function migrateFile(string $file): bool
    {
        try {
            $this->install();

            $file = Config::instance()->getBasePath() . $this->toRelative($file);
            if (!is_readable($file)) return false;
            if ($this->hasPath($this->toRelative($file))) return false;

            if (!$this->backupFile) {   // only run once per session.
                $options = Db::parseDsn(Config::instance()->get('db.mysql'));
                $this->backupFile = Db\DbBackup::save(Config::instance()->getTempPath(), $options);
            }
            if (preg_match('/\.php$/i', basename($file))) {  // Include .php files
                $callback = include $file;
                if (is_callable($callback)) {
                    $callback();
                }
                $this->insertPath($file);
                return true;
            } else {  // is sql
                // replace any table prefix
                $sql = file_get_contents($file);
                if (!strlen(trim($sql))) return false;

                $stm = Db::getPdo()->prepare($sql);
                $stm->execute();

                // Bugger of a way to get the error:
                // https://stackoverflow.com/questions/23247553/how-can-i-get-an-error-when-running-multiple-queries-with-pdo
                $i = 0;
                do {
                    $i++;
                } while ($stm->nextRowset());

                $error = $stm->errorInfo();
                if ($error[0] != "00000") {
                    throw new \Tk\Db\Exception("Query $i failed: " . $error[2], 0, $sql);
                }
                $this->insertPath($file);
                return true;
            }

        } catch (\Exception $e){
            Log::debug($e->__toString());
        }
        return false;
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
        return rtrim(str_replace(Config::instance()->getBasePath(), '', $path), '/');
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