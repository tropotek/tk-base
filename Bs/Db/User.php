<?php
namespace Bs\Db;

use Bs\Db\Traits\RoleTrait;
use Tk\Db\Map\Model;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class User extends Model implements UserIface
{

    use Traits\TimestampTrait;
    use RoleTrait;

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
    public $username = '';

    /**
     * @var string
     */
    public $password = '';

    /**
     * @var string
     */
    public $nameFirst = '';

    /**
     * @var string
     */
    public $nameLast = '';

    /**
     * @var string
     */
    public $email = '';

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
     * User constructor.
     */
    public function __construct()
    {
        $this->_TimestampTrait();
        $this->setIp($this->getConfig()->getRequest()->getClientIp());
    }

    /**
     * @return User
     */
    public static function createGuest()
    {
        $user = new self();
        $user->setUsername('guest');
        $user->setNameFirst('Guest');
        $user->setRoleId(Role::TYPE_PUBLIC);
        return $user;
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
        if ($this->getImage()) {
            if (filter_var($this->getImage(), \FILTER_SANITIZE_URL, \FILTER_FLAG_PATH_REQUIRED)) {
                $url = \Tk\Uri::create($this->getImage());
            } else if (file_exists($this->getConfig()->getDataPath() . $this->getImage())) {
                $url = \Tk\Uri::create($this->getConfig()->getDataUrl() . $this->getImage());
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
     * returns the concatenated first and last name values
     *
     * @return string
     */
    public function getName(): string
    {
        $name = trim($this->getNameFirst() . ' ' . $this->getNameLast());
        if (!$name) $name = $this->getUsername();
        return $name;
    }

    /**
     * @param string $name
     * @return User
     */
    public function setName(?string $name): User
    {
        \Tk\Log::warning('Using deprecated function.');
        $this->setNameFirst(substr($name, 0, strpos($name, '')));
        $this->setNameLast(substr($name, strpos($name, '')+1));
        return $this;
    }

    /**
     * @return string
     */
    public function getNameFirst(): string
    {
        return $this->nameFirst;
    }

    /**
     * @param string $nameFirst
     * @return User
     */
    public function setNameFirst(string $nameFirst): User
    {
        $this->nameFirst = $nameFirst;
        return $this;
    }

    /**
     * @return string
     */
    public function getNameLast(): string
    {
        return $this->nameLast;
    }

    /**
     * @param string $nameLast
     * @return User
     */
    public function setNameLast(string $nameLast): User
    {
        $this->nameLast = $nameLast;
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
        $key = sprintf('%s%s', $this->getVolatileId(), $this->getUsername());
        if ($isTemp) {
            $key .= date('-YmdHis');
        }
        return $this->getConfig()->hash($key);
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
            $pwd = $this->getConfig()->createPassword(10);
        }
        $this->setPassword($this->getConfig()->hashPassword($pwd, $this));
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
        return !$this->getRole() || $this->getRole()->hasType(Role::TYPE_PUBLIC);
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
        $usermap = $this->getConfig()->getUserMapper();

        if (!$this->getRoleId()) {
            $errors['roleId'] = 'Invalid field role value';
        } else {
            try {
                $role = $this->getRole();
                if (!$role) throw new \Tk\Exception('Please select a valid role.');
            } catch (\Exception $e) {
                $errors['roleId'] = $e->getMessage();
            }
        }

        if (!$this->getUsername()) {
            $errors['username'] = 'Invalid field username value';
        } else {
            $dup = $usermap->findByUsername($this->getUsername());
            if ($dup && $dup->getId() != $this->getId()) {
                $errors['username'] = 'This username is already in use';
            }
        }
        if ($this->getEmail()) {
            if (!filter_var($this->getEmail(), FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Please enter a valid email address';
            } else {
                $dup = $usermap->findByEmail($this->getEmail());
                if ($dup && $dup->getId() != $this->getId()) {
                    $errors['email'] = 'This email is already in use';
                }
            }
        }
        if ($this->getUid()) {
            $dup = $usermap->findByUid($this->getUid());
            if ($dup && $dup->getId() != $this->getId()) {
                $errors['uid'] = 'This UID is already in use';
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
