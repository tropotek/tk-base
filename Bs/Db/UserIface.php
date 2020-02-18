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
     * @return \Tk\Db\Data
     */
    public function getData();

    /**
     * @return int
     */
    public function getUid();

    /**
     * @return string
     */
    public function getType();

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
     * @return \DateTime|null
     */
    public function getLastLogin();


}
