<?php
namespace Bs\Db;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2018 Michael Mifsud
 */
interface RoleIface
{

    /**
     * @return int
     */
    public function getId();

    /**
     * @return string
     */
    public function getName();

    /**
     * @return string
     */
    public function getType();

    /**
     * @param string|array $type
     * @return bool
     */
    public function hasType($type);

    /**
     * @return bool
     */
    public function isActive();

    /**
     * If true this role is readonly
     * @return bool
     */
    public function isStatic();


    /**
     * @return array
     */
    public function getPermissions();

    /**
     * @param string $name
     * @return RoleIface
     */
    public function addPermission($name);

    /**
     * @param string $name
     * @return RoleIface
     */
    public function removePermission($name);

    /**
     * Note: Be sure to check the active status of this role
     *       and return false if this is a non active role.
     *
     * @param string $name
     * @return bool
     */
    public function hasPermission($name);


}