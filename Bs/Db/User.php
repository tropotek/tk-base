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

    use Traits\TimestampTrait;

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
        $this->_TimestampTrait();
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
        if ($this->isPublic()) return;
        $this->getHash();
        $this->getData()->save();
        parent::save();
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
     * Get the data object
     *
     * @return \Tk\Db\Data
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
     * @return \Tk\Uri
     * @throws \Exception
     */
    public function getImageUrl()
    {
        $url = \Tk\Uri::create('/html/app/img/user.png');
        if ($this->image) {
            if (filter_var($this->image, \FILTER_SANITIZE_URL, \FILTER_FLAG_PATH_REQUIRED)) {
                $url = \Tk\Uri::create($this->image);
            } else if (file_exists($this->getConfig()->getDataPath() . $this->image)) {
                $url = \Tk\Uri::create($this->getConfig()->getDataUrl() . $this->image);
            }
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
            $b64 = base64_encode($img->getContents());
            $url = \Tk\Uri::create('data:image/png;base64,' . $b64);
        }
        return $url;
    }


    /**
     * @return int
     */
    public function getRoleId(): int
    {
        return $this->roleId;
    }

    /**
     * @param int $roleId
     * @return User
     */
    public function setRoleId(int $roleId): User
    {
        $this->roleId = $roleId;
        return $this;
    }

    /**
     * @return string
     */
    public function getUid(): string
    {
        return $this->uid;
    }

    /**
     * @param string $uid
     * @return User
     */
    public function setUid(?string $uid): User
    {
        $this->uid = $uid;
        return $this;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param string $username
     * @return User
     */
    public function setUsername(?string $username): User
    {
        $this->username = $username;
        return $this;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     * @return User
     */
    public function setPassword(string $password): User
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return User
     */
    public function setName(?string $name): User
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     * @return User
     */
    public function setEmail(?string $email): User
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return string
     */
    public function getPhone(): string
    {
        return $this->phone;
    }

    /**
     * @param string $phone
     * @return User
     */
    public function setPhone(?string $phone): User
    {
        $this->phone = $phone;
        return $this;
    }

    /**
     * @return string
     */
    public function getImage(): string
    {
        return $this->image;
    }

    /**
     * @param string $image
     * @return User
     */
    public function setImage(?string $image): User
    {
        $this->image = $image;
        return $this;
    }

    /**
     * @return string
     */
    public function getNotes(): string
    {
        return $this->notes;
    }

    /**
     * @param string|null $notes
     * @return User
     */
    public function setNotes(?string $notes): User
    {
        $this->notes = $notes;
        return $this;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * @param bool $active
     * @return User
     */
    public function setActive(bool $active): User
    {
        $this->active = $active;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getLastLogin(): \DateTime
    {
        return $this->lastLogin;
    }

    /**
     * @param \DateTime $lastLogin
     * @return User
     */
    public function setLastLogin(?\DateTime $lastLogin): User
    {
        $this->lastLogin = $lastLogin;
        return $this;
    }

    /**
     * @return string
     */
    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    /**
     * @param string $sessionId
     * @return User
     */
    public function setSessionId(?string $sessionId): User
    {
        $this->sessionId = $sessionId;
        return $this;
    }

    /**
     * @return string
     */
    public function getIp(): string
    {
        return $this->ip;
    }

    /**
     * @param string $ip
     * @return User
     */
    public function setIp(?string $ip): User
    {
        $this->ip = $ip;
        return $this;
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
     * @param string|string[] $permission
     * @return bool
     */
    public function hasPermission($permission)
    {
        if (!$this->getRole()) return false;
        return $this->getRole()->hasPermission($permission);
    }


    /**
     * @return boolean
     */
    public function isAdmin()
    {
        return $this->hasPermission(Permission::TYPE_ADMIN);
    }

    /**
     * @return boolean
     */
    public function isUser()
    {
        return $this->hasPermission(Permission::TYPE_USER);
    }

    /**
     * @return boolean
     */
    public function isPublic()
    {
        return !$this->getRole();
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
            $errors['roleId'] = 'Invalid field role value';
        } else {
            try {
                $role = $this->getRole();
                if (!$role) throw new \Tk\Exception('Please select a valid role.');
            } catch (\Exception $e) {
                $errors['roleId'] = $e->getMessage();
            }
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
     * @return string
     * @deprecated removing roleType over time Use the Permission object instead.
     */
    public function getRoleType()
    {
        //\Tk\Log::warning('Deprecated: User::getRoleType()');
        if (!$this->getRole()) return '';
        return $this->getRole()->getType();
    }

    /**
     * @param string|array $roleType
     * @return boolean
     * @deprecated Use getRole()->hasType() or getRoleType()
     */
    public function hasRole($roleType)
    {
        \Tk\Log::warning('Deprecated: User::hasRole($role)');
        if (!is_array($roleType)) $roleType = array($roleType);
        foreach ($roleType as $r) {
            if ($r == $this->getRoleType() || preg_match('/'.preg_quote($r).'/', $this->getRoleType())) {
                return true;
            }
        }
        return false;
    }
}
