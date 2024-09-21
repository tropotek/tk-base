<?php
namespace Au;

use Bs\Factory;

class Masquerade
{

    /**
     * Session ID
     */
    const SID = '__msq__';

    /**
     * Query string to initiate masquerading
     */
    const QUERY_MSQ = 'msq';

    /**
     * Add a callable function to check if a user can masquerade as another user
     * function (User $user, User $msqUser): bool { ... }
     * @var ?callable
     */
    public static mixed $CAN_MASQUERADE = null;


    /**
     * Masquerade as another user
     * return true on success, remember to redirect to the required page on success
     *
     * @todo: store the current session in the masquerade array, then reset the session when logging out
     */
    public static function masqueradeLogin(Auth $auth, Auth $msqAuth): bool
    {
        if (!self::canMasqueradeAs($auth, $msqAuth)) return false;
        $factory = Factory::instance();

        // Get the masquerade queue from the session
        $msqArr = $_SESSION[static::SID] ?? [];

        // Save the current user and url to the session, to allow logout
        $userData = [
            'identity' => $auth->username,
            'url' => \Tk\Uri::create()->toString(),
        ];
        $msqArr[] = $userData;

        // Save the updated masquerade queue
        $_SESSION[static::SID] = $msqArr;

        // Simulates an AuthAdapter authenticate() method
        $factory->getAuthController()->getStorage()->write($msqAuth->username);

        return true;
    }

    /**
     * Log out of the current masquerading user
     * Redirects to the url the user was last on
     *
     * @todo: store the current session in the masquerade array, then reset the session when logging out
     */
    public static function masqueradeLogout(): bool
    {
        $factory = Factory::instance();
        if (!self::isMasquerading()) return false;
        if (!$factory->getAuthController()->hasIdentity()) return false;
        $msqArr = $_SESSION[self::SID];
        if (!is_array($msqArr) || !count($msqArr)) return false;

        $userData = array_pop($msqArr);
        if (empty($userData['identity']) || empty($userData['url'])) return false;

        // Save the updated masquerade queue
        $_SESSION[self::SID] = $msqArr;
        $factory->getAuthController()->getStorage()->write($userData['identity']);

        \Tk\Uri::create($userData['url'])->remove(self::QUERY_MSQ)->redirect();
        return true;
    }

    /**
     * Check if this user can masquerade as the supplied msqUser
     * Returns true if user is admin and not already masquerading as selected user
     */
    public static function canMasqueradeAs(Auth $auth, Auth $msqAuth): bool
    {
        if (!$msqAuth->active) return false;
        if ($auth->authId == $msqAuth->authId) return false;
        // Check if we are already masquerading as this user in the queue
        $msqArr = $_SESSION[static::SID] ?? null;
        if (is_array($msqArr)) {
            foreach ($msqArr as $data) {
                if ($data['identity'] == $msqAuth->username) return false;
            }
        }
        if ($auth->isAdmin()) return true;
        if (is_callable(self::$CAN_MASQUERADE)) {
            return boolval(call_user_func_array(self::$CAN_MASQUERADE, [$auth, $msqAuth]));
        }
        return false;
    }

    /**
     * Get the user who is masquerading
     */
    public static function getMasqueradingUser(): ?Auth
    {
        if (is_array($_SESSION[static::SID] ?? false)) {
            $msqArr = $_SESSION[static::SID][0];
            return Auth::findByUsername($msqArr['identity']);
        }
        return null;
    }

    /**
     * Is this user currently masquerading
     */
    public static function isMasquerading(): bool
    {
        return (self::getNestings() > 0);
    }

    /**
     * Return the total masquerading nesting's (if any)
     *
     * 0 if not masquerading
     * >0 The masquerading total (for nested masquerading)
     *
     */
    public static function getNestings(): int
    {
        return count($_SESSION[static::SID] ?? []);
    }

    /**
     * logout of all masquerading users
     */
    public static function clearAll(): void
    {
        unset($_SESSION[static::SID]);
    }
}
