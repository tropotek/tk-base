<?php
namespace Bs;

use Tk\Db;

/**
 * This will hold any persistent system configuration values.
 *
 * After changing any Registry values remember to call save() to store the updated registry.
 *
 * NOTE: Objects should not be saved in the Registry storage, only primitive types.
 */
final class Registry extends Db\Collection
{
    public static string   $DB_TABLE  = 'registry';
    protected static mixed $_instance = null;


    public function __construct()
    {
        parent::__construct(self::$DB_TABLE);
        $this->load();
    }

    public static function instance(): self
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Static alias for self::instance()->get(...)
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        return self::instance()->get($key, $default);
    }

    /**
     * Static alias for self::instance()->set(...)
     */
    public static function setValue(string $key, mixed $value): static
    {
        return self::instance()->set($key, $value);
    }

    public static function getSiteName(): string
    {
        return self::instance()->get('site.name', '');
    }

    public static function getSiteShortName(): string
    {
        return self::instance()->get('site.name.short', '');
    }

    public static function getSiteEmail(): string
    {
        return self::instance()->get('site.email', '');
    }

    public static function isMaintenanceMode(): bool
    {
        return (bool)self::instance()->get('system.maintenance.enabled', false);
    }

    public static function setMaintenanceMode(bool $b = true): static
    {
        self::instance()->set('system.maintenance.enabled', $b);
        self::instance()->save();
        return self::instance();
    }

}