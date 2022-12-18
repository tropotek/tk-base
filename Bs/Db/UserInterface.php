<?php
namespace Bs\Db;

use Tk\Db\Mapper\ModelInterface;

/**
 * @author Tropotek <http://www.tropotek.com/>
 */
interface UserInterface extends ModelInterface
{

    /**
     * Return a user object located by the identity saved in the Auth Adapter
     *
     * NOTE: By default the $identity is the username and the results are found in the user_auth table
     */
    public static function findByIdentity(string $identity): mixed;

    /**
     * Return a data path to a location for users specific files
     */
    //public function getDataPath(): string;

    /**
     * return true if this user type = or is in the supplied type(s)
     */
    public function hasType(string|array $type): bool;

    /**
     * A user type (ie: admin, member, staff, student, etc)
     */
    public function getType(): string;

    public function getUsername(): string;

    /**
     * Return the user title. EG: Dr, Ms, Mr, etc
     */
    public function getTitle(): string;

    /**
     * This should return the concatenated strings:
     * $this->>getTitle() . ' ' . $this->getFirstName() . ' ' . $this->>getLastName()
     */
    public function getName(): string;

    public function getFirstName(): string;

    public function getLastName(): string;

    public function getEmail(): string;

    public function isActive(): bool;

    /**
     * A unique has to identify the user in URLS and external comm`s
     */
    public function getHash(): string;

    /**
     * Get this users local timezone for date formatting
     * Should default to system timezone if none found
     * @link https://php.net/manual/en/datetimezone.construct.php
     */
    public function getTimezone(): string;

    public function getLastLogin(): ?\DateTime;



    // TODO: add setters for account registration only, just the minimum required setters only





//    /**
//     * @return array
//     */
//    public function getPermissions();
//
//    /**
//     * @param string $name
//     * @return $this
//     */
//    public function addPermission($name);
//
//    /**
//     * @param string $name (optional) If omitted then all permissions are removed
//     * @return $this
//     */
//    public function removePermission($name = null);
//
//    /**
//     * Check if this object has the requested permission
//     *
//     * @param string|string[] $permission
//     * @return bool
//     */
//    public function hasPermission($permission);

}
