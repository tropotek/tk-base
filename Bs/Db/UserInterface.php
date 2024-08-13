<?php
namespace Bs\Db;

use Tk\Db\Mapper\ModelInterface;
use Tk\Uri;

/**
 * @deprecated To be removed, Use \Bs\Db\User as a base object to check
 */
interface UserInterface extends ModelInterface
{
	const PERM_ADMIN            = 0x1; // All permissions

    /**
	 * permissions are bit masks that can include on or more bits
	 * requests for permission are ANDed with the user's permissions
	 * if the result is non-zero the user has permission.
     * EG:
     *   const PERM_ADMIN            = 0x00000001; // All permissions
     *   const PERM_SYSADMIN         = 0x00000002; // Change system settings
     *   const PERM_MANAGE_STAFF     = 0x00000004; // Manage staff users
     *   const PERM_MANAGE_USER      = 0x00000008; // Manage base users
     */
    public function getPermissions(): int;

    /**
     * Check if a user has permissions
     * EG:
     * ```
     * // non-logged in users have no permissions
     * if (!$this->isActive()) return false;
     * // admin users have all permissions
     * if ((self::PERM_ADMIN & $this->getPermissions()) != 0) return true;
     * return ($permission & $this->getPermissions()) != 0;
     * ```
     */
    public function hasPermission(int $permission): bool;

    /**
     * A check to see if this user can masquerade as the supplied user
     */
    public function canMasqueradeAs(User $msqUser): bool;

    /**
     * return true if this user type = or is in the supplied type(s)
     */
    public function isType(string|array $type): bool;

    /**
     * A user role (ie: admin, member, staff, student, etc)
     */
    public function getType(): string;

    public function getUsername(): string;

    public function getEmail(): string;

    /**
     * This should return the users full name as:
     * $this->>getNameTitle() . ' ' . $this->getFirstName() . ' ' . $this->>getLastName()
     */
    public function getName(): string;

    public function getNameTitle(): string;

    public function getNameFirst(): string;

    public function getNameLast(): string;

    /**
     * Get a user's profile/logo image
     */
    public function getImageUrl(): ?Uri;

    /**
     * Get this users local timezone for date formatting
     * Should default to system timezone if none found
     * @link https://php.net/manual/en/datetimezone.construct.php
     */
    public function getTimezone(): ?string;

    public function getLastLogin(): ?\DateTime;

    public function isActive(): bool;

    /**
     * A unique has to identify the user in URLS and external comm`s
     */
    public function getHash(): string;

}
