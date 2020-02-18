<?php
namespace Bs\Db;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Permission
{
    /**
     *
     */
    const MANAGE_SITE               = 'perm.manage.site';

    /**
     *
     */
    const MANAGE_PLUGIN             = 'perm.manage.plugin';

    /**
     *
     */
    const MANAGE_USER               = 'perm.manage.user';


    /**
     * Get a list of default permissions when creating or resetting a users permissions
     *
     * @param string $type
     * @return array
     */
    public static function getDefaultPermissions($type)
    {
        switch ($type) {
            case User::TYPE_ADMIN:
                return array(
                    self::MANAGE_SITE,
                    self::MANAGE_PLUGIN,
                    self::MANAGE_USER
                );
            case User::TYPE_USER:
                return array();
        }
        return array();
    }

}
