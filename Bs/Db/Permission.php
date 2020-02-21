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
     * @param bool $removeTypes This removes any type permissions as they are deprecated for ver 4.0
     * @return array
     */
    public static function getPermissionList($type = '', $removeTypes = true)
    {
        return array();
    }


}
