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

class User extends Model // implements UserInterface, FileInterface
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
     */
	const PERM_ADMIN            = 0x1; // Admin
	const PERM_SYSADMIN         = 0x2; // Change system
	const PERM_MANAGE_STAFF     = 0x4; // Manage staff
    const PERM_MANAGE_MEMBER    = 0x8; // Manage members
	//                            0x10; // available

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
     * Basic site user
     */
    const TYPE_MEMBER = 'member';

	const TYPE_LIST = [
        self::TYPE_STAFF            => "Staff",
        self::TYPE_MEMBER           => "Member",
    ];

    public int        $userId        = 0;
    public string     $uid           = '';
    public string     $type          = self::TYPE_MEMBER;
    public int        $permissions   = 0;
    public string     $username      = '';
    public string     $password      = '';
    public string     $email         = '';
    public string     $nameTitle     = '';
    public string     $nameFirst     = '';
    public string     $nameLast      = '';
    public ?string    $nameDisplay   = '';
    public string     $notes         = '';
    public ?string    $timezone      = null;
    public bool       $active        = true;
    public string     $sessionId     = '';
    public string     $hash          = '';      // todo: chould get this from the view
    public ?\DateTime $lastLogin     = null;

    public \DateTime $modified;
    public \DateTime $created;


    public function __construct()
    {
        $this->_TimestampTrait();
        $this->timezone = $this->getConfig()->get('php.date.timezone');
    }

    public function save(): void
    {
        $this->getHash();

        if (!$this->username && $this->email) {
            $this->username = $this->email;
        }
        // Remove permissions for non-staff users
        if ($this->isType(self::TYPE_MEMBER)) {
            $this->permissions = 0;
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
            $user->sessionId = '';
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
        $img = Image::createAvatar($this->getName() ?: $this->username, $color);
        $b64 = base64_encode($img->getContents());
        return Uri::create('data:image/png;base64,' . $b64);
    }

    public function getHomeUrl(): Uri
    {
        return Uri::create('/dashboard');
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
            if (trim($r) == trim($this->type)) {
                return true;
            }
        }
        return false;
    }

    public function hasPermission(int $permission): bool
    {
        // non-logged in users have no permissions
        if (!$this->active) return false;
        // admin users have all permissions
        if ((self::PERM_ADMIN & $this->permissions) != 0) return true;
        return ($permission & $this->permissions) != 0;
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

    public function canMasqueradeAs(User $msqUser): bool
    {
        if ($this->isAdmin()) return true;
        if ($this->isStaff() && $msqUser->isType(self::TYPE_MEMBER)) return true;
        return false;
    }

    /**
     * @todo Move this to the view when model updated
     */
    public function getName(bool $withTitle = false): string
    {
        $name = [];
        if ($withTitle) {
            if ($this->nameTitle) $name[] = $this->nameTitle;
        }
        if ($this->nameFirst) $name[] = $this->nameFirst;
        if ($this->nameLast) $name[] = $this->nameLast;
        return implode(' ', $name);
    }

    /**
     * Use this to populate a select field for a users title
     * @todo Move these into a constant property
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

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
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

        if (!$this->username) {
            $errors['username'] = 'Invalid field username value';
        } else {
            $dup = $mapper->findByUsername($this->username);
            if ($dup && $dup->userId != $this->userId) {
                $errors['username'] = 'This username is already in use';
            }
        }

        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address';
        } else {
            $dup = $mapper->findByEmail($this->email);
            if ($dup && $dup->userId != $this->userId) {
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
        $message->addTo($this->email);
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
                    Factory::instance()->getAuthController()->getStorage()->write($user->username);
                }
            }
        }
        return $user;
    }
}
