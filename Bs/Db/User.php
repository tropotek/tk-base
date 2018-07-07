<?php
namespace Bs\Db;

use Tk\Db\Map\Model;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class User extends Model implements \Tk\ValidInterface
{
    const ROLE_ADMIN = 'admin';
    const ROLE_USER = 'user';

    /**
     * @var int
     */
    public $id = 0;

    /**
     * @var string
     */
    public $name = '';

    /**
     * @var string
     */
    public $email = '';

    /**
     * @var string
     */
    public $username = '';

    /**
     * @var string
     */
    public $password = '';

    /**
     * @var string
     */
    public $role = '';

    /**
     * @var string
     */
    public $notes = '';

    /**
     * @var \DateTime
     */
    public $lastLogin = null;

    /**
     * @var bool
     */
    public $active = true;

    /**
     * @var string
     */
    public $hash = '';

    /**
     * @var \DateTime
     */
    public $modified = null;

    /**
     * @var \DateTime
     */
    public $created = null;

    /**
     * @var string
     */
    public $ip = '';


    /**
     * User constructor.
     *
     */
    public function __construct()
    {
        $this->modified = new \DateTime();
        $this->created = new \DateTime();
        $this->ip = \App\Config::getInstance()->getRequest()->getIp();
    }

    /**
     * @throws \Tk\Db\Exception
     */
    public function save()
    {
        if (!$this->hash) {
            $this->hash = $this->generateHash();
        }
        parent::save();
    }

    /**
     * Return the users home|dashboard relative url
     *
     * @return string
     */
    public function getHomeUrl()
    {
        if ($this->isAdmin())
            return \Tk\Uri::create('/admin/index.html');
        if ($this->isUser())
            return \Tk\Uri::create('/user/index.html');
        return \Tk\Uri::create('/index.html');
    }

    /**
     * Set the password from a plain string
     *
     * @param string $pwd
     * @return User
     */
    public function setNewPassword($pwd = '')
    {
        if (!$pwd) {
            $pwd = \Tk\Config::createPassword(10);
        }
        $this->password = \App\Config::getInstance()->hashPassword($pwd, $this);
        return $this;
    }

    /**
     * Helper method to generate user hash
     *
     * @param bool $isTemp Set this to true, when generate a temporary hash used for registration
     * @return string
     * @throws \Tk\Db\Exception
     */
    public function generateHash($isTemp = false)
    {
        $key = sprintf('%s%s', $this->getVolatileId(), $this->username);
        if ($isTemp) {
            $key .= date('-YmdHis');
        }
        return hash('md5', $key);
    }


    /**
     * @param string|array $role
     * @return boolean
     */
    public function hasRole($role)
    {
        if (!is_array($role)) $role = array($role);
        foreach ($role as $r) {
            if ($r == $this->role || preg_match('/'.preg_quote($r).'/', $this->role)) {
                return true;
            }
        }
        return false;
    }

    /**
     *
     * @return boolean
     */
    public function isAdmin()
    {
        return $this->hasRole(\App\Db\User::ROLE_ADMIN);
    }

    /**
     *
     * @return boolean
     */
    public function isUser()
    {
        return $this->hasRole(\App\Db\User::ROLE_USER);
    }


    /**
     * Validate this object's current state and return an array
     * with error messages. This will be useful for validating
     * objects for use within forms.
     *
     * @return array
     * @throws \Tk\Db\Exception
     */
    public function validate()
    {
        $errors = array();

//        if (!$this->name) {
//            $errors['name'] = 'Invalid field name value';
//        }
        if (!$this->role) {
            $errors['role'] = 'Invalid field role value';
        }
        if (!$this->username) {
            $errors['username'] = 'Invalid field username value';
        } else {
            $dup = UserMap::create()->findByUsername($this->username);
            if ($dup && $dup->getId() != $this->getId()) {
                $errors['username'] = 'This username is already in use';
            }
        }
        if ($this->email) {
            if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Please enter a valid email address';
            } else {
                $dup = UserMap::create()->findByEmail($this->email);
                if ($dup && $dup->getId() != $this->getId()) {
                    $errors['email'] = 'This email is already in use';
                }
            }
        }
        return $errors;
    }
}
