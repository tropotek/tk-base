<?php
namespace Bs\Db;

use Bs\Factory;
use Bs\Util\Masquerade;
use Bs\Db\Traits\TimestampTrait;
use Tk\Color;
use Tk\Config;
use Tk\Image;
use Tk\Uri;
use Tk\Db;
use Tk\Db\Filter;
use Tk\Db\Model;

class User extends Model
{
    use TimestampTrait;

    public static string $USER_CLASS = self::class;

    /**
     * The remember me cookie name
     */
    const REMEMBER_CID = '__rmb';

    const TYPE_STAFF = 'staff';
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
    public ?string    $name          = '';
    public string     $nameTitle     = '';
    public string     $nameFirst     = '';
    public string     $nameLast      = '';
    public ?string    $nameDisplay   = '';
    public string     $notes         = '';
    public string     $timezone      = '';
    public bool       $active        = true;
    public string     $sessionId     = '';
    public string     $hash          = '';
    public string     $dataPath      = '';
    public ?\DateTime $lastLogin     = null;

    public \DateTime $modified;
    public \DateTime $created;


    public function __construct()
    {
        $this->_TimestampTrait();
        $this->timezone = Config::instance()->get('php.date.timezone');
    }

    public static function create(): static
    {
        return new self::$USER_CLASS();
    }

    public function save(): void
    {
        $map = static::getDataMap();
        $values = $map->getArray($this);

        if (!$this->username && $this->email) {
            $this->username = $this->email;
        }

        // Remove permissions for non-staff users
        if ($this->isType(self::TYPE_MEMBER)) {
            $this->permissions = 0;
        }

        if ($this->userId) {
            $values['user_id'] = $this->userId;
            Db::update('user', 'user_id', $values);
        } else {
            unset($values['user_id']);
            Db::insert('user', $values);
            $this->userId = Db::getLastInsertId();
        }

        $this->reload();
    }

    public function delete(): bool
    {
        return (false !== Db::delete('user', ['user_id' => $this->userId]));
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

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public function getFileList(array $filter = []): array
    {
        $filter += ['model' => $this];
        return File::findFiltered($filter);
    }

    public function getDataPath(): string
    {
        return $this->dataPath;
    }

    public function getImageUrl(): ?Uri
    {
        $color = Color::createRandom($this->userId);
        $img = Image::createAvatar($this->getName() ?: $this->username, $color);
        $b64 = base64_encode($img->getContents());
        return Uri::create('data:image/png;base64,' . $b64);
    }

    public function getHomeUrl(): Uri
    {
        $homes = Config::instance()->get('user.homepage', '/');
        return Uri::create($homes[$this->type] ?? '/');
    }

    public function isAdmin(): bool
    {
        return ($this->isStaff() && $this->hasPermission(Permissions::PERM_ADMIN));
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
        if ((Permissions::PERM_ADMIN & $this->permissions) != 0) return true;
        return ($this->permissions & $permission) != 0;
    }

    /**
     * return a list of individual permission values
     * Use for select lists, or anywhere you need to list
     * the permissions and lookup their names
     */
    public function getPermissionList(): array
    {
        return array_keys(array_filter(Factory::instance()->getPermissions(), fn($k) => ($k & $this->permissions), ARRAY_FILTER_USE_KEY));
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
            if ($dup && $dup->userId != $this->userId) {
                $errors['username'] = 'This username is already in use';
            }
        }

        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address';
        } else {
            $dup = self::findByEmail($this->email);
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
        if (Config::instance()->isDev()) return $errors;

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


    public function rememberMe(int $day = 30): void
    {
        [$selector, $validator, $token] = self::generateToken();

        // remove all existing token associated with the user id
        self::deleteToken($this->userId);

        // set expiration date
        $expires_sec = time() + 60 * 60 * 24 * $day;
        $expiry = date('Y-m-d H:i:s', $expires_sec);
        // insert a token to the database
        $hash_validator = password_hash($validator, PASSWORD_DEFAULT);
        if (self::insertToken($this->userId, $selector, $hash_validator, $expiry)) {
            Factory::instance()->getCookie()->set(self::REMEMBER_CID, $token, $expires_sec);
        }
    }

    /**
     * Remove the `remember me` cookie
     */
    public function forgetMe(): void
    {
        self::deleteToken($this->userId);
        Factory::instance()->getCookie()->delete(self::REMEMBER_CID);
    }

    /**
     * Attempt to find a user by the cookie
     * If the user checked the `remember me` checkbox at login this should find the user
     * if a user is found it will be automatically logged into the auth controller
     */
    public static function retrieveMe(): ?static
    {
        $user = null;
        $token = $_COOKIE[self::REMEMBER_CID] ?? '';
        //$token = Factory::instance()->getRequest()->cookies->get(self::REMEMBER_CID, '');

        if ($token) {
            [$selector, $validator] = self::parseToken($token);
            $tokens = self::findTokenBySelector($selector);
            if ($tokens && password_verify($validator, $tokens['hashed_validator'])) {
                $user = self::findBySelector($selector);
                if ($user) {
                    Factory::instance()->getAuthController()->getStorage()->write($user->username);
                }
            }
        }
        return $user;
    }


    public static function find(int $userId): ?static
    {
        return Db::queryOne("
            SELECT *
            FROM v_user
            WHERE user_id = :userId",
            compact('userId'),
            self::$USER_CLASS
        );
    }

    public static function findAll(): array
    {
        return Db::query("
            SELECT *
            FROM v_user",
            [],
            self::$USER_CLASS
        );
    }

    public static function findByUsername(string $username): ?static
    {
        return self::findFiltered(['username' => $username])[0] ?? null;
    }

    public static function findByEmail(string $email): ?static
    {
        return self::findFiltered(['email' => $email])[0] ?? null;
    }

    public static function findByHash(string $hash): ?static
    {
        return self::findFiltered(['hash' => $hash])[0] ?? null;
    }

    public static function findBySelector(string $selector): ?static
    {
        return Db::queryOne("
            SELECT *
            FROM v_user u
            INNER JOIN user_remember z USING (user_id)
            WHERE z.selector = :selector AND expiry > NOW()",
            compact('selector'),
            self::$USER_CLASS
        );
    }

    public static function findFiltered(array|Filter $filter): array
    {
        $filter = Filter::create($filter);

        if (!empty($filter['search'])) {
            $filter['search'] = '%' . $filter['search'] . '%';
            $w  = 'LOWER(a.name_first) LIKE LOWER(:search) OR ';
            $w .= 'LOWER(a.name_last) LIKE LOWER(:search) OR ';
            $w .= 'LOWER(a.name_display) LIKE LOWER(:search) OR ';
            $w .= 'LOWER(a.email) LIKE LOWER(:search) OR ';
            $w .= 'LOWER(a.uid) LIKE LOWER(:search) OR ';
            $w .= 'LOWER(a.user_id) LIKE LOWER(:search) OR ';
            if ($w) $filter->appendWhere('(%s) AND ', substr($w, 0, -3));
        }

        if (!empty($filter['id'])) {
            $filter['userId'] = $filter['id'];
        }
        if (!empty($filter['userId'])) {
            if (!is_array($filter['userId'])) $filter['userId'] = [$filter['userId']];
            $filter->appendWhere('a.user_id IN :userId AND ');
        }

        if (!empty($filter['exclude'])) {
            if (!is_array($filter['exclude'])) $filter['exclude'] = [$filter['exclude']];
            $filter->appendWhere('a.user_id NOT IN :exclude AND ', $filter['exclude']);
        }

        if (!empty($filter['uid'])) {
            $filter->appendWhere('a.uid = :uid AND ');
        }

        if (!empty($filter['hash'])) {
            $filter->appendWhere('a.hash = :hash AND ');
        }

        if (!empty($filter['type'])) {
            if (!is_array($filter['type'])) $filter['type'] = [$filter['type']];
            $filter->appendWhere('a.type IN :type AND ');
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

        return Db::query("
            SELECT *
            FROM v_user a
            {$filter->getSql()}",
            $filter->all(),
            self::$USER_CLASS
        );
    }

    /*
     * Functions to manage the "remember me" tokens
     * https://www.phptutorial.net/php-tutorial/php-remember-me/
     */

    /**
     * Generate a pair of random tokens called selector and validator
     */
    public static function generateToken(): array
    {
        $selector = bin2hex(random_bytes(16));
        $validator = bin2hex(random_bytes(32));
        return [$selector, $validator, $selector . ':' . $validator];
    }

    /**
     * Split a token stored in the cookie into selector and validator
     */
    public static function parseToken(string $token): ?array
    {
        $parts = explode(':', $token);
        if ($parts && count($parts) == 2) {
            return [$parts[0], $parts[1]];
        }
        return null;
    }

    /**
     * Add a new row to the user_remember table
     */
    public static function insertToken(int $user_id, string $selector, string $hashed_validator, string $expiry): int|bool
    {
        $browser_id = Factory::instance()->getCookie()->getBrowserId();
        return Db::insert('user_remember', compact('user_id', 'browser_id', 'selector', 'hashed_validator', 'expiry'));
    }

    /**
     * Find a row in the user_remember table by a selector.
     * It only returns the match selector if the token is not expired
     *   by comparing the expiry with the current time
     */
    public static function findTokenBySelector(string $selector): array
    {
        $browser_id = Factory::instance()->getCookie()->getBrowserId();
        $sql = 'SELECT id, selector, hashed_validator, browser_id, user_id, expiry
            FROM user_remember
            WHERE selector = :selector
            AND browser_id = :browser_id
            AND expiry >= NOW()
            LIMIT 1';
        return (array)Db::queryOne($sql, compact('selector', 'browser_id'));
    }

    public static function findTokenByUserId(string $user_id): array
    {
        $browser_id = Factory::instance()->getCookie()->getBrowserId();
        $sql = 'SELECT id, selector, hashed_validator, user_id, expiry
            FROM user_remember
            WHERE user_id = :user_id
            AND browser_id = :browser_id
            AND expiry >= NOW()
            LIMIT 1';
        return (array)Db::queryOne($sql, compact('user_id', 'browser_id'));
    }

    public static function deleteToken(int $user_id): bool|int
    {
        $browser_id = Factory::instance()->getCookie()->getBrowserId();
        return Db::delete('user_remember', compact('user_id', 'browser_id'));
    }
}
