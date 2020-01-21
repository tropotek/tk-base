<?php
namespace Bs\Db;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2018 Michael Mifsud
 */
interface UserIface extends \Tk\ValidInterface, \Tk\Db\ModelInterface
{


    /**
     * @return int
     */
    public function getRoleId();

    /**
     * @return int
     */
    public function getUid();

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
     * This should return the concatenated strings:
     * $this->getNameFirst() . ' ' . $this->>getNameLast()
     *
     * @return string
     */
    public function getName();

    /**
     * @return string
     */
    public function getNameFirst();

    /**
     * @return string
     */
    public function getNameLast();

    /**
     * @return string
     */
    public function getEmail();

    /**
     * @return string
     */
    public function getPhone();

    /**
     * @return string
     */
    public function getImage();

    /**
     * @return string
     */
    public function getImageUrl();

    /**
     * @return bool
     */
    public function isActive();

    /**
     * @return string
     */
    public function getHash();

    /**
     * @return RoleIface
     */
    public function getRole();

    /**
     * @return \DateTime|null
     */
    public function getLastLogin();



    /**
     * NOTE: All old $user->getRole() calls changed to $user->getRoleType() or $user->getRole()->getType()
     * @return string
     * @deprecated I want to eventually use the permission system not this function
     */
    public function getRoleType();

}