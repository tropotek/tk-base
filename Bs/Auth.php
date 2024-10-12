<?php
namespace Bs;

use Bs\Db\Masquerade;
use Bs\Db\Remember;
use Bs\Db\UserInterface;
use Bs\Traits\ForeignModelTrait;
use Bs\Traits\TimestampTrait;
use Tk\Config;
use Tk\ObjectUtil;
use Tk\Uri;
use Tk\Db;
use Tk\Db\Filter;
use Tk\Db\Model;

/**
 * This object manages user authentication within the DB
 * In your app create User Models that use the \Bs\Traits\AuthTrait
 * And when creating new users create an Auth object to store the users credentials
 *
 * You Apps user models should focus on the user data not authentication data
 * Create a view for your model that links the auth fields to have access to permissions, username, email, etc.
 *
 */
class Auth extends Model
{
    use ForeignModelTrait;
    use TimestampTrait;

    const int PERM_NONE             = 0;
    const int PERM_ADMIN            = 0x1;

    /**
     * valid options for externally created accounts
     */
    const string EXT_MICROSOFT  = 'microsoft';
    const string EXT_GOOGLE     = 'google';
    const string EXT_FACEBOOK   = 'facebook';


    public int        $authId        = 0;
    public string     $uid           = '';
    public string     $fkey          = '';
    public int        $fid           = 0;
    public int        $permissions   = 0;
    public string     $username      = '';
    public string     $password      = '';
    public string     $email         = '';
    public string     $timezone      = '';
    public bool       $active        = true;
    /**
     * value set if account created when logged in by external SSI/oAuth
     */
    public string     $external      = '';
    public string     $sessionId     = '';
    public string     $hash          = '';
    public ?\DateTime $lastLogin     = null;
    public \DateTime  $modified;
    public \DateTime  $created;


    public function __construct()
    {
        $this->_TimestampTrait();
        $this->timezone = Config::instance()->get('php.date.timezone');
    }

    public function save(): void
    {
        $map = static::getDataMap();

        if (!$this->username && $this->email) {
            $this->username = $this->email;
        }

        $values = $map->getArray($this);
        if ($this->authId) {
            $values['auth_id'] = $this->authId;
            Db::update('auth', 'auth_id', $values);
        } else {
            unset($values['auth_id']);
            Db::insert('auth', $values);
            $this->authId = Db::getLastInsertId();
        }

        $this->reload();
    }

    public static function create(Model $model): self
    {
        $id = self::getDbModelId($model);
        $obj = new self();
        $obj->fkey     = $model::class;
        $obj->fid      = $id;
        $obj->timezone = Config::instance()->get('php.date.timezone');
        return $obj;
    }

    /**
     * Get the currently logged in user
     */
    public static function getAuthUser(): ?self
    {
        static $authUser = null;
        if (is_null($authUser)) {
            $auth = Factory::instance()->getAuthController();
            if ($auth->hasIdentity()) {
                $authUser = self::findByUsername($auth->getIdentity());
            }
            if ($authUser instanceof self && !$authUser->active) {
                self::logout($authUser);
                $authUser = null;
                Uri::create('/')->redirect();
            }
        }
        return $authUser;
    }

    /**
     * @param bool $cookie If true any stored login cookies will also be removed
     */
    public static function logout(Auth $authUser = null, bool $cookie = true): void
    {
        if (!$authUser) $authUser = Auth::getAuthUser();
        if ($authUser) {
            if (Masquerade::isMasquerading()) {
                Masquerade::masqueradeLogout();
                return;
            }
            Factory::instance()->getAuthController()->clearIdentity();
            if ($cookie) {
                Remember::forgetMe($authUser->authId);
            }
            $authUser->sessionId = '';
            $authUser->save();
            session_destroy();
        }
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public function getHomeUrl(): Uri
    {
        /** @var UserInterface $user */
        $user = $this->getDbModel();
        return $user->getHomeUrl();
    }

    public function hasPermission(int $permission): bool
    {
        // non-logged in users have no permissions
        if (!$this->active) return false;
        // admin users have all permissions
        if ((self::PERM_ADMIN & $this->permissions) != 0) return true;
        return ($this->permissions & $permission) != 0;
    }

    public function isAdmin(): bool
    {
        return $this->hasPermission(self::PERM_ADMIN);
    }

    /**
     * Validate this object's current state and return an array
     * with error messages. This will be useful for validating
     * objects for use within forms.
     */
    public function validate(): array
    {
        $errors = [];

        if (!$this->username) {
            $errors['username'] = 'Invalid field username value';
        } else {
            $dup = self::findByUsername($this->username);
            if ($dup && $dup->authId != $this->authId) {
                $errors['username'] = 'This username is already in use';
            }
        }

        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address';
        } else {
            $dup = self::findByEmail($this->email);
            if ($dup && $dup->authId != $this->authId) {
                $errors['email'] = 'This email is already in use';
            }
        }

        if (!class_exists($this->fkey) || !is_subclass_of($this->fkey, UserInterface::class)) {
            $errors['fkey'] = 'Invalid foreign key value';
        }

        if (!$this->fid) {
            $errors['fid'] = 'Invalid foreign key id';
        }

        return $errors;
    }

    public static function validatePassword(string $pwd, array &$errors = []): array
    {
        if (!Config::instance()->get('auth.password.strict', true)) return $errors;

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


    public static function find(int $authId): ?self
    {
        return Db::queryOne("
            SELECT *
            FROM v_auth
            WHERE auth_id = :authId",
            compact('authId'),
            self::class
        );
    }

    /**
     * @return array<int,Auth>
     */
    public static function findAll(): array
    {
        return Db::query("
            SELECT *
            FROM v_auth",
            [],
            self::class
        );
    }

    public static function findByUsername(string $username): ?self
    {
        $username = trim($username);
        if(empty($username)) return null;
        return Db::queryOne("
            SELECT *
            FROM v_auth
            WHERE username = :username",
            compact('username'),
            self::class
        );
    }

    public static function findByEmail(string $email): ?self
    {
        $email = trim($email);
        if(empty($email)) return null;
        return Db::queryOne("
            SELECT *
            FROM v_auth
            WHERE email = :email",
            compact('email'),
            self::class
        );
    }

    public static function findByHash(string $hash): ?self
    {
        $hash = trim($hash);
        if(empty($hash)) return null;
        return Db::queryOne("
            SELECT *
            FROM v_auth
            WHERE hash = :hash",
            compact('hash'),
            self::class
        );
    }

    /**
     * Find user using remember me token
     */
    public static function findBySelector(string $selector): ?self
    {
        $selector = trim($selector);
        if(empty($selector)) return null;
        return Db::queryOne("
            SELECT *
            FROM v_auth u
            INNER JOIN auth_remember z USING (auth_id)
            WHERE z.selector = :selector
            AND u.active
            AND expiry > NOW()",
            compact('selector'),
            self::class
        );
    }

    public static function findByModel(Model $model): ?self
    {
        $fkey = get_class($model);
        $fid = self::getDbModelId($model);
        return self::findByModelId($fkey, $fid);
    }

    public static function findByModelId(string $fkey, int $fid): ?self
    {
        return Db::queryOne("
            SELECT *
            FROM v_auth
            WHERE fkey = :fkey
            AND fid = :fid",
            compact('fkey', 'fid'),
            self::class
        );
    }

    /**
     * @return array<int,Auth>
     */
    public static function findFiltered(array|Filter $filter): array
    {
        $filter = Filter::create($filter);

        if (!empty($filter['search'])) {
            $filter['search'] = '%' . $filter['search'] . '%';
            $w  = 'LOWER(a.email) LIKE LOWER(:search) OR ';
            $w .= 'LOWER(a.uid) LIKE LOWER(:search) OR ';
            $w .= 'LOWER(a.auth_id) LIKE LOWER(:search) OR ';
            $filter->appendWhere('(%s) AND ', substr($w, 0, -3));
        }

        if (!empty($filter['authId'])) {
            if (!is_array($filter['authId'])) $filter['authId'] = [$filter['authId']];
            $filter->appendWhere('a.auth_id IN :authId AND ');
        }

        if (!empty($filter['uid'])) {
            $filter->appendWhere('a.uid = :uid AND ');
        }

        if (!empty($filter['model']) && $filter['model'] instanceof Model) {
            $filter['fid'] = self::getDbModelId($filter['model']);
            $filter['fkey'] = get_class($filter['model']);
        }
        if (isset($filter['fid'])) {
            $filter->appendWhere('a.fid = :fid AND ');
        }
        if (isset($filter['fkey'])) {
            $filter->appendWhere('a.fkey = :fkey AND ');
        }

        if (!empty($filter['hash'])) {
            $filter->appendWhere('a.hash = :hash AND ');
        }

        if (!empty($filter['username'])) {
            $filter->appendWhere('a.username = :username AND ');
        }

        if (!empty($filter['email'])) {
            $filter->appendWhere('a.email = :email AND ');
        }

        if (!empty($filter['active'])) {
            $filter['active'] = truefalse($filter['active']);
            $filter->appendWhere('active = :active AND ');
        }

        if (!empty($filter['exclude'])) {
            if (!is_array($filter['exclude'])) $filter['exclude'] = [$filter['exclude']];
            $filter->appendWhere('a.auth_id NOT IN :exclude AND ', $filter['exclude']);
        }

        return Db::query("
            SELECT *
            FROM v_auth a
            {$filter->getSql()}",
            $filter->all(),
            self::class
        );
    }

}
