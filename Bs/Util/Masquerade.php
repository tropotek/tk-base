<?php
namespace Bs\Util;

use Bs\Db\User;
use Bs\Factory;
use Tk\Traits\SystemTrait;

class Masquerade
{
    use SystemTrait;

    /**
     * Session ID
     */
    const SID = '__msq__';

    /**
     * Query string to initiate masquerading
     */
    const QUERY_MSQ = 'msq';


    /**
     * Masquerade as another user
     * return true on success, remember to redirect to the required page on success
     */
    public static function masqueradeLogin(User $user, User $msqUser): bool
    {
        if (!self::canMasqueradeAs($user, $msqUser)) return false;
        $factory = Factory::instance();

        // Get the masquerade queue from the session
        $msqArr = $_SESSION[static::SID] ?? [];

        // Save the current user and url to the session, to allow logout
        $userData = [
            'userId' => $user->username,
            'url' => \Tk\Uri::create()->toString(),
        ];
        $msqArr[] = $userData;

        // Save the updated masquerade queue
        $_SESSION[static::SID] = $msqArr;

        // Simulates an AuthAdapter authenticate() method
        $factory->getAuthController()->getStorage()->write($msqUser->username);

        return true;
    }

    /**
     * Log out of the current masquerading user
     * Redirects to the url the user was last on
     */
    public static function masqueradeLogout(): bool
    {
        $factory = Factory::instance();
        if (!self::isMasquerading()) return false;
        if (!$factory->getAuthController()->hasIdentity()) return false;
        $msqArr = $_SESSION[self::SID];
        if (!is_array($msqArr) || !count($msqArr)) return false;

        $userData = array_pop($msqArr);
        if (empty($userData['userId']) || empty($userData['url'])) return false;

        // Save the updated masquerade queue
        $_SESSION[self::SID] = $msqArr;
        $factory->getAuthController()->getStorage()->write($userData['userId']);

        \Tk\Uri::create($userData['url'])->remove(self::QUERY_MSQ)->redirect();
        return true;
    }

    /**
     * Check if this user can masquerade as the supplied msqUser
     */
    public static function canMasqueradeAs(User $user, User $msqUser): bool
    {
        if (!$msqUser->active) return false;
        if ($user->userId == $msqUser->userId) return false;

        $msqArr = $_SESSION[static::SID] ?? null;
        if (is_array($msqArr)) {    // Check if we are already masquerading as this user in the queue
            foreach ($msqArr as $data) {
                if ($data['userId'] == $msqUser->userId) return false;
            }
        }
        return $user->canMasqueradeAs($msqUser);
    }

    /**
     * Get the user who is masquerading, ignoring any nested masqueraded users
     */
    public static function getMasqueradingUser(): ?User
    {
        $user = null;
        if (is_array($_SESSION[static::SID])) {
            $msqArr = $_SESSION[static::SID][0];
            /** @var User $user */
            $user = User::find($msqArr['userId']);
        }
        return $user;
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
