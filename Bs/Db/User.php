<?php
namespace Bs\Db;

use Tk\Db\Map\Model;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class User extends Model implements UserIface
{


    /**
     * @var int
     */
    public $id = 0;

    /**
     * @var int
     */
    public $roleId = 0;

    /**
     * @var string
     */
    public $uid = '';

    /**
     * @var string
     */
    public $username = 'guest';

    /**
     * @var string
     */
    public $password = '';

    /**
     * @var string
     */
    public $name = 'Guest';

    /**
     * @var string
     */
    public $email = 'guest@noreply.com';

    /**
     * @var string
     */
    public $phone = '';

    /**
     * @var string
     */
    public $image = '';

    /**
     * @var string
     */
    public $notes = '';

    /**
     * @var bool
     */
    public $active = true;

    /**
     * @var \DateTime
     */
    public $lastLogin = null;

    /**
     * @var string
     */
    public $sessionId = '';

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
     * @var \Tk\Db\Data
     */
    private $data = null;

    /**
     * @var RoleIface
     */
    private $role = null;


    /**
     * User constructor.
     */
    public function __construct()
    {
        $this->modified = new \DateTime();
        $this->created = new \DateTime();
        $this->ip = \Bs\Config::getInstance()->getRequest()->getIp();
    }

    /**
     * @return \Tk\Db\Map\Mapper|UserMap
     */
    public function getMapper()
    {
        return self::createMapper();
    }

    /**
     * @throws \Exception
     */
    public function save()
    {
        $this->getHash();
        $this->getData()->save();
        parent::save();
    }

    /**
     * Get the data object
     *
     * @return \Tk\Db\Data
     * @throws \Exception
     */
    public function getData()
    {
        if (!$this->data)
            $this->data = \Tk\Db\Data::create(get_class($this), $this->getVolatileId(), 'user_data');
        return $this->data;
    }

    /**
     * Get the path for all file associated to this object
     *
     * @return string
     */
    public function getDataPath()
    {
        return sprintf('/user/%s', $this->getVolatileId());
    }

    /**
     * @return \Tk\Uri|string
     * @throws \Exception
     */
    public function getImageUrl()
    {
        if ($this->image) {
            return \Tk\Uri::create($this->getConfig()->getDataUrl() . $this->getDataPath() . $this->image);
        } else if (class_exists('\LasseRafn\InitialAvatarGenerator\InitialAvatar')) {
            $color = \Tk\Color::createRandom($this->getVolatileId());
            $avatar = new \LasseRafn\InitialAvatarGenerator\InitialAvatar();
            $img = $avatar->name($this->getName())
                ->length(2)
                ->fontSize(0.5)
                ->size(96)// 48 * 2
                ->background($color->toString(true))
                ->color($color->getTextColor()->toString(true))
                ->generate()
                ->stream('png', 100);
            return 'data:image/png;base64,' . base64_encode($img->getContents());
        }
        return \Tk\Uri::create('/html/app/img/user.png');
    }


    /**
     * @return int
     */
    public function getRoleId()
    {
        return $this->roleId;
    }

    /**
     * @return int
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Return the users hashed password
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @return string
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @return \DateTime|null
     */
    public function getLastLogin()
    {
        return $this->lastLogin;
    }



    /**
     * Get the user hash or generate one if needed
     *
     * @return string
     */
    public function getHash()
    {
        if (!$this->hash) {
            $this->hash = $this->generateHash();
        }
        return $this->hash;
    }

    /**
     * Helper method to generate user hash
     *
     * @param bool $isTemp Set this to true, when generate a temporary hash used for registration
     * @return string
     */
    public function generateHash($isTemp = false)
    {
        $key = sprintf('%s%s', $this->getVolatileId(), $this->username);
        if ($isTemp) {
            $key .= date('-YmdHis');
        }
        return \Bs\Config::getInstance()->hash($key);
    }

    /**
     * Set the password from a plain string
     *
     * @param string $pwd
     * @return User
     */
    public function setNewPassword($pwd = '')
    {
        $config = \Bs\Config::getInstance();
        if (!$pwd) {
            $pwd = $config->createPassword(10);
        }
        $this->password = $config->hashPassword($pwd, $this);
        return $this;
    }

    /**
     * @return RoleIface
     */
    public function getRole()
    {
        if (!$this->role) {
            try {
                $this->role = \Bs\Config::getInstance()->getRoleMapper()->find($this->roleId);
            } catch (\Exception $e) {
                \Tk\Log::warning('No valid role found for UID: ' . $this->getId());
                $this->role = new Role();
            }
        }
        return $this->role;
    }


    /**
     * Validate this object's current state and return an array
     * with error messages. This will be useful for validating
     * objects for use within forms.
     *
     * @return array
     * @throws \Exception
     */
    public function validate()
    {
        $errors = array();

        if (!$this->roleId) {
            $errors['roleId'] = 'Invalid field roleId value';
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

    /**
     * @return boolean
     * @deprecated use getRole()->hasType('..')
     */
    public function isAdmin()
    {
        return $this->getRole()->hasPermission(Permission::PERM_ADMIN);
        //return $this->getRole()->hasType(Role::TYPE_ADMIN);
    }

    /**
     * @return boolean
     * @deprecated use getRole()->hasType('..')
     */
    public function isUser()
    {
        return $this->getRole()->hasPermission(Permission::TYPE_USER);
        //return $this->getRole()->hasType(Role::TYPE_USER);
    }

    /**
     * @return boolean
     * @deprecated use getRole()->hasType('..')
     */
    public function isPublic()
    {
        return (!$this->getRole() || !$this->getRole()->getType() || $this->getRole()->hasType(Role::TYPE_PUBLIC));
    }





    /**
     * @return string
     * @deprecated removing roleType over time
     */
    public function getRoleType()
    {
        \Tk\Log::warning('Deprecated: User::getRoleType()');
        return $this->getRole()->getType();
    }

    /**
     * @param string|array $role
     * @return boolean
     * @deprecated Use getRole()->hasType() or getRoleType()
     */
    public function hasRole($role)
    {
        \Tk\Log::warning('Deprecated: User::hasRole($role)');
        if (!is_array($role)) $role = array($role);
        foreach ($role as $r) {
            if ($r == $this->getRoleType() || preg_match('/'.preg_quote($r).'/', $this->getRoleType())) {
                return true;
            }
        }
        return false;
    }
}
