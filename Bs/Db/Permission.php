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
     * Get all available permissions for a user type
     *
     * @param string $type (optional) If set returns only the permissions for that user type otherwise returns all permissions
     * @return array
     */
    public static function getPermissionList($type = '')
    {
        return array();
    }

    /**
     * Return the default permission set for creating new user types.
     *
     * @param string $type
     * @return array
     */
    public static function getDefaultPermissionList($type = '')
    {
        return self::getPermissionList($type);
    }


}
