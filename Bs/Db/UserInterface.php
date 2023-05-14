<?php
namespace Bs\Db;

use Tk\Db\Mapper\ModelInterface;

interface UserInterface extends ModelInterface
{

    /**
     * return true if this user type = or is in the supplied type(s)
     */
    public function isType(string|array $role): bool;

    /**
     * A user role (ie: admin, member, staff, student, etc)
     */
    public function getType(): string;

    public function getUsername(): string;

    /**
     * This should return the concatenated strings:
     * $this->>getTitle() . ' ' . $this->getFirstName() . ' ' . $this->>getLastName()
     */
    public function getName(): string;

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
    public function getTimezone(): ?string;

    public function getLastLogin(): ?\DateTime;


    public function getPermissions(): int;

    public function hasPermission(int $permission): bool;

}
