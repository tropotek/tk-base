<?php
namespace Bs\Db;

use Bs\Factory;
use Bs\Util\Masquerade;
use Bs\Db\Traits\HashTrait;
use Bs\Db\Traits\TimestampTrait;
use Tk\Color;
use Tk\Config;
use Tk\Db\Mapper\Model;
use Tk\Db\Mapper\Result;
use Tk\Encrypt;
use Tk\Image;
use Tk\Uri;

class User extends Model implements UserInterface, FileInterface
{
    use TimestampTrait;
    use HashTrait;

    /**
     * The remember me cookie name
     */
    const REMEMBER_CID = '__rmb';

    /**
     * permission values
	 * permissions are bit masks that can include on or more bits
	 * requests for permission are ANDed with the user's permissions
	 * if the result is non-zero the user has permission.
     *
     * high-level permissions for specific roles
     */
	const PERM_ADMIN            = 0x00000001; // Admin
	const PERM_SYSADMIN         = 0x00000002; // Change system
	const PERM_MANAGE_STAFF     = 0x00000004; // Manage staff
    const PERM_MANAGE_MEMBER    = 0x00000008; // Manage members
	//                            0x00000010; // available

	/**
     * permission groups and descriptions
     */
	const PERMISSION_LIST = [
        self::PERM_ADMIN            => "Admin",
        self::PERM_SYSADMIN         => "Manage Settings",
        self::PERM_MANAGE_STAFF     => "Manage Staff",
        self::PERM_MANAGE_MEMBER    => "Manage Users",
    ];

    /**
     * Site staff user
     */
    const TYPE_STAFF = 'staff';

    /**
     * Base logged-in user type (Access to user pages)
     */
    const TYPE_MEMBER = 'member';

	/**
     * User type list
     */
	const TYPE_LIST = [
        self::TYPE_STAFF            => "Staff",
        self::TYPE_MEMBER           => "Member",
    ];


    public int $userId = 0;

    public string $uid = '';

    public string $type = self::TYPE_MEMBER;

    public int $permissions = 0;

    public string $username = '';

    public string $password = '';

    public string $email = '';

    //public string $name = '';

    public string $nameTitle = '';

    public string $nameFirst = '';

    public string $nameLast = '';

    public string $nameDisplay = '';

    public string $notes = '';

    public ?string $timezone = null;

    public bool $active = true;

    public string $sessionId = '';

    public string $hash = '';

    public ?\DateTime $lastLogin = null;

    public ?\DateTime $modified = null;

    public ?\DateTime $created = null;


    public function __construct()
    {
        $this->_TimestampTrait();
        $this->timezone = $this->getConfig()->get('php.date.timezone');
    }


    public function save(): void
    {
        $this->getHash();

        if (!$this->getUsername() && $this->getEmail()) {
            $this->setUsername($this->getEmail());
        }
        // Remove permissions for non-staff users
        if ($this->isType(self::TYPE_MEMBER)) {
            $this->setPermissions(0);
        }
        parent::save();
    }

    /**
     * @param bool $cookie If true any stored login cookies will also be removed
     */
    public static function logout(bool $cookie = true): void
    {
        $user = Factory::instance()->getAuthUser();
        if ($user) {
            if (Masquerade::isMasquerading()) {
                Masquerade::masqueradeLogout();
                return;
            }
            Factory::instance()->getAuthController()->clearIdentity();
            if ($cookie) {
                $user->forgetMe();
            }
            $user->setSessionId('');
            $user->save();
            Uri::create()->redirect();
        }
    }

    public function getFileList(array $filter = [], ?\Tk\Db\Tool $tool = null): Result
    {
        $filter += ['model' => $this];
        return FileMap::create()->findFiltered($filter, $tool);
    }

    public function getDataPath(): string
    {
        return sprintf('/user/%s/data', $this->getVolatileId());
    }

    public function getImageUrl(): ?Uri
    {
        $color = Color::createRandom($this->getVolatileId());
        $img = Image::createAvatar($this->getUsername(), $color);
        $b64 = base64_encode($img->getContents());
        return Uri::create('data:image/png;base64,' . $b64);
    }

    public function getHomeUrl(): Uri
    {
        return Uri::create('/dashboard');
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): User
    {
        $this->userId = $userId;
        return $this;
    }

    public function getUid(): string
    {
        return $this->uid;
    }

    public function setUid(string $uid): static
    {
        $this->uid = $uid;
        return $this;
    }

    public function isAdmin(): bool
    {
        return ($this->isStaff() && $this->hasPermission(self::PERM_ADMIN));
    }

    public function isStaff(): bool
    {
        return $this->isType(self::TYPE_STAFF);
    }

    public function isMember(): bool
    {
        return $this->isType(self::TYPE_MEMBER);
    }

    public function isType(string|array $type): bool
    {
        if (!is_array($type)) $type = [$type];
        foreach ($type as $r) {
            if (trim($r) == trim($this->getType())) {
                return true;
            }
        }
        return false;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function hasPermission(int $permission): bool
    {
		// non-logged in users have no permissions
		if (!$this->isActive()) return false;
		// admin users have all permissions
		if ((self::PERM_ADMIN & $this->getPermissions()) != 0) return true;
		return ($permission & $this->getPermissions()) != 0;
    }

    public function getPermissions(): int
    {
        return $this->permissions;
    }

    public function setPermissions(int $permissions): static
    {
        $this->permissions = $permissions;
        return $this;
    }

    /**
     * return a list of individual permission values
     * Use for select lists, or anywhere you need to list
     * the permissions and lookup their names
     */
    public function getPermissionList(): array
    {
        return array_keys(array_filter(static::PERMISSION_LIST, fn($k) => ($k & $this->permissions), ARRAY_FILTER_USE_KEY));
    }

    /**
     * return a list of all available permissions for this user
     * This is here, so we can get access to permissions from subclasses
     * NOT: this may be refactored in the future
     */
    public function getAvailablePermissions(): array
    {
        if ($this->isStaff()) {
            return static::PERMISSION_LIST;
        }
        return [];
    }

    public function canMasqueradeAs(UserInterface $msqUser): bool
    {
        if ($this->isAdmin()) return true;
        if ($this->isStaff() && $msqUser->isType(self::TYPE_MEMBER)) return true;
        return false;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(?string $username): static
    {
        $this->username = $username;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;
        return $this;
    }

//    public function getName(): string
//    {
//        return $this->name;
//    }
//
//    public function setName(string $name): User
//    {
//        $this->name = $name;
//        return $this;
//    }

    public function getName(bool $withTitle = false): string
    {
        $name = [];
        if ($withTitle) {
            if ($this->getNameTitle()) $name[] = $this->getNameTitle();
        }
        if ($this->getNameFirst()) $name[] = $this->getNameFirst();
        if ($this->getNameLast()) $name[] = $this->getNameLast();
        return implode(' ', $name);
    }

    public function setName(?string $name): static
    {
        $name = trim($name);
        if (preg_match('/\s/',$name)) {
            $this->setNameFirst(substr($name, 0, strpos($name, ' ')));
            $this->setNameLast(substr($name, strpos($name, ' ') + 1));
        } else {
            $this->setNameFirst($name);
        }
        return $this;
    }

    /**
     * Use this to populate a select field for a users title
     */
    public static function getTitleList(): array
    {
        $arr = array('Mr', 'Mrs', 'Ms', 'Dr', 'Prof', 'Esq', 'Hon', 'Messrs',
            'Mmes', 'Msgr', 'Rev', 'Jr', 'Sr', 'St');
        $arr = array_combine($arr, $arr);
        $titles = array();
        foreach ($arr as $k => $v) {
            $titles[$k . '.'] = $v;
        }
        return $titles;
    }

    public function getNameTitle(): string
    {
        return $this->nameTitle;
    }

    public function setNameTitle(string $nameTitle): User
    {
        $this->nameTitle = $nameTitle;
        return $this;
    }

    public function getNameFirst(): string
    {
        return $this->nameFirst;
    }

    public function setNameFirst(string $nameFirst): User
    {
        $this->nameFirst = $nameFirst;
        return $this;
    }

    public function getNameLast(): string
    {
        return $this->nameLast;
    }

    public function setNameLast(string $nameLast): User
    {
        $this->nameLast = $nameLast;
        return $this;
    }

    public function getNameDisplay(): string
    {
        return $this->nameDisplay;
    }

    public function setNameDisplay(string $nameDisplay): User
    {
        $this->nameDisplay = $nameDisplay;
        return $this;
    }

    public function getNotes(): string
    {
        return $this->notes;
    }

    public function setNotes(string $notes): static
    {
        $this->notes = $notes;
        return $this;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function setTimezone(?string $timezone): static
    {
        $this->timezone = $timezone;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;
        return $this;
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    public function setSessionId(string $sessionId): static
    {
        $this->sessionId = $sessionId;
        return $this;
    }

    public function getLastLogin(): ?\DateTime
    {
        return $this->lastLogin;
    }

    public function setLastLogin(?\DateTime $lastLogin): static
    {
        $this->lastLogin = $lastLogin;
        return $this;
    }

    /**
     * Validate this object's current state and return an array
     * with error messages. This will be useful for validating
     * objects for use within forms.
     */
    public function validate(): array
    {
        $errors = [];
        $mapper = $this->getMapper();

        if (!$this->getUsername()) {
            $errors['username'] = 'Invalid field username value';
        } else {
            $dup = $mapper->findByUsername($this->getUsername());
            if ($dup && $dup->getId() != $this->getId()) {
                $errors['username'] = 'This username is already in use';
            }
        }

        if (!filter_var($this->getEmail(), FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address';
        } else {
            $dup = $mapper->findByEmail($this->getEmail());
            if ($dup && $dup->getId() != $this->getId()) {
                $errors['email'] = 'This email is already in use';
            }
        }

        if (!$this->getName()) {
            $errors['name'] = 'Invalid field value';
        }
        return $errors;
    }

    public static function validatePassword(string $pwd, array &$errors = []): array
    {
        if (Config::instance()->isDebug()) return $errors;

        if (strlen($pwd) < 8) {
            $errors[] = "Password too short";
        }

        if (!preg_match("#[0-9]+#", $pwd)) {
            $errors[] = "Must include at least one number";
        }

        if (!preg_match("#[a-zA-Z]+#", $pwd)) {
            $errors[] = "Must include at least one letter";
        }

        if( !preg_match("#[A-Z]+#", $pwd) ) {
            $errors[] = "Must include at least one capital";
        }

        if( !preg_match("#\W+#", $pwd) ) {
            $errors[] = "Must include at least one symbol";
        }

        return $errors;
    }

    public function sendRecoverEmail(bool $isNewAccount = false): bool
    {
        // send email to user
        $content = <<<HTML
            <h2>Account Recovery.</h2>
            <p>
              Welcome {name}
            </p>
            <p>
              Please follow the link to finish recovering your account password.<br/>
              <a href="{activate-url}" target="_blank">{activate-url}</a>
            </p>
            <p><small>Note: If you did not initiate this email, you can safely disregard this message.</small></p>
        HTML;

        $message = $this->getFactory()->createMessage();
        $message->set('content', $content);
        $message->setSubject($this->getConfig()->get('site.title') . ' Password Recovery');
        $message->addTo($this->getEmail());
        $message->set('name', $this->getName());

        $hashToken = Encrypt::create($this->getConfig()->get('system.encrypt'))->encrypt(serialize(['h' => $this->getHash(), 't' => time()]));
        $url = Uri::create('/recoverUpdate')->set('t', $hashToken);
        $message->set('activate-url', $url->toString());

        return $this->getFactory()->getMailGateway()->send($message);
    }


    public function rememberMe(int $day = 30): void
    {
        [$selector, $validator, $token] = $this->getMapper()->generateToken();

        // remove all existing token associated with the user id
        $this->getMapper()->deleteToken($this->getId());

        // set expiration date
        $expires_sec = time() + 60 * 60 * 24 * $day;
        $expiry = date('Y-m-d H:i:s', $expires_sec);
        // insert a token to the database
        $hash_validator = password_hash($validator, PASSWORD_DEFAULT);
        if ($this->getMapper()->insertToken($this->getId(), $selector, $hash_validator, $expiry)) {
            $this->getCookie()->set(self::REMEMBER_CID, $token, $expires_sec);
        }
    }

    /**
     * Remove the `remember me` cookie
     */
    public function forgetMe(): void
    {
        $this->getMapper()->deleteToken($this->getId());
        $this->getCookie()->delete(self::REMEMBER_CID);
    }

    /**
     * Attempt to find a user by the cookie
     * If the user checked the `remember me` checkbox at login this should find the user
     * if a user is found it will be automatically logged into the auth controller
     */
    public static function retrieveMe(): ?User
    {
        $map = Factory::instance()->getUserMap();
        $user = null;
        $token = Factory::instance()->getRequest()->cookies->get(self::REMEMBER_CID, '');
        if ($token) {
            [$selector, $validator] = $map->parseToken($token);
            $tokens = $map->findTokenBySelector($selector);
            if ($tokens && password_verify($validator, $tokens['hashed_validator'])) {
                $user = $map->findBySelector($selector);
                if ($user) {
                    Factory::instance()->getAuthController()->getStorage()->write($user->getUsername());
                }
            }
        }
        return $user;
    }
}
