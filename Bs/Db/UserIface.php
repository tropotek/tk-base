<?php
namespace Bs\Db;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2018 Michael Mifsud
 */
interface UserIface
{

    /**
     * @return int
     */
    public function getId();

    /**
     * @return string
     */
    public function getHash();

    /**
     * @return string
     */
    public function getName();

    /**
     * @return string
     */
    public function getUsername();

    /**
     * Return the users hashed password
     * @return string
     */
    public function getPassword();

    /**
     * @return string
     */
    public function getEmail();

    /**
     * @return RoleIface
     */
    public function getRole();

    /**
     * NOTE: All old $user->getRole() calls changed to $user->getRoleType() or $user->getRole()->getType()
     * @return string
     */
    public function getRoleType();

    /**
     * @return bool
     */
    public function isActive();



}