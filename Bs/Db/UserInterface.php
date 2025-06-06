<?php
namespace Bs\Db;

use Tk\Uri;

interface UserInterface {

    /**
     * return the currently logged in user
     */
    public static function getAuthUser(): ?self;

    /**
     * return a URI of the user home page based on its type
     */
    public static function getHomeUrl(string $type = ''): Uri;

    /**
     * check this user has the required permissions
     */
    public function hasPermission(int $permission): bool;
}

