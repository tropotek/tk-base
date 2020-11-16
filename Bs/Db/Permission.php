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
     * Can manage site settings and configuration
     * @target user,admin
     */
    const MANAGE_SITE        = 'perm.manage.site';

    /**
     * Developer User Permissions
     */
    const IS_DEVELOPER = 'perm.developer';

    /**
     * Can masquerade as other lower tier users
     * @target user,admin
     */
    const CAN_MASQUERADE        = 'perm.masquerade';

    /**
     * Manage plugins
     * @target user,admin
     */
    const MANAGE_PLUGINS        = 'perm.manage.plugins';


    /**
     * Get all available permissions for a user type
     *
     * @param string $type (optional) If set returns only the permissions for that user type otherwise returns all permissions
     * @return array
     */
    public static function getPermissionList($type = '')
    {
        $arr = array();
        switch ($type) {
            case User::TYPE_ADMIN;
                $arr = array(
                    'Manage Site Config' => self::MANAGE_SITE,
                    'Manage Site Plugins' => self::MANAGE_PLUGINS,
                    'Can Masquerade' => self::CAN_MASQUERADE
                );
                break;
            case User::TYPE_MEMBER:
                $arr = array();
                break;
            default:
                $arr = array(
                    'Manage Site Config' => self::MANAGE_SITE,
                    'Manage Site Plugins' => self::MANAGE_PLUGINS,
                    'Can Masquerade' => self::CAN_MASQUERADE
                );
        }
        return $arr;
    }

    /**
     * Return the default permission set for creating new user types.
     *
     * @param string $type
     * @return array
     */
    public static function getDefaultPermissionList($type = '')
    {
        $list = self::getPermissionList($type);
        if ($type == User::TYPE_ADMIN) {
            $list = array(
                'Manage Site Config' => self::MANAGE_SITE,
                'Manage Site Plugins' => self::MANAGE_PLUGINS,
                'Can Masquerade' => self::CAN_MASQUERADE
            );
        }
        return $list;
    }


}
