<?php

namespace Bs\Db;

class Permissions
{

    /**
     * permission values
     * permissions are bit masks that can include on or more bits
     * requests for permission are ANDed with the user's permissions
     * if the result is non-zero the user has permission.
     */
    const PERM_ADMIN            = 0x1; // Admin
    const PERM_SYSADMIN         = 0x2; // Change system settings
    const PERM_MANAGE_STAFF     = 0x4; // Manage staff
    const PERM_MANAGE_MEMBERS   = 0x8; // Manage members
    //                            0x10; // available

    const PERMISSION_LIST = [
        self::PERM_ADMIN            => "Admin",
        self::PERM_SYSADMIN         => "Manage Settings",
        self::PERM_MANAGE_STAFF     => "Manage Staff",
        self::PERM_MANAGE_MEMBERS   => "Manage Members",
    ];

}