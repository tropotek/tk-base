<?php
namespace Au;

use Bs\Factory;
use Tk\Db;

/**
 * Functions to manage the "remember me" tokens
 *
 * https://www.phptutorial.net/php-tutorial/php-remember-me/
 */
class Remember
{
    /**
     * The remember me cookie name
     */
    const REMEMBER_CID = '__rmb';


    public static function rememberMe(int $authId, int $day = 30): void
    {
        [$selector, $validator, $token] = self::generateToken();

        // remove all existing token associated with the user id
        self::deleteToken($authId);

        // set expiration date
        $expires_sec = time() + 60 * 60 * 24 * $day;
        $expiry = date('Y-m-d H:i:s', $expires_sec);
        // insert a token to the database
        $hash_validator = password_hash($validator, PASSWORD_DEFAULT);
        if (self::insertToken($authId, $selector, $hash_validator, $expiry)) {
            Factory::instance()->getCookie()->set(self::REMEMBER_CID, $token, $expires_sec);
        }
    }

    public static function forgetMe(int $authId): void
    {
        self::deleteToken($authId);
        Factory::instance()->getCookie()->delete(self::REMEMBER_CID);
    }

    /**
     * Attempt to find a user by the cookie
     * If the user checked the `remember me` checkbox at login this should find the user
     * if a user is found it will be automatically logged into the auth controller
     */
    public static function retrieveMe(): ?Auth
    {
        $token = $_COOKIE[self::REMEMBER_CID] ?? '';
        if ($token) {
            [$selector, $validator] = self::parseToken($token);
            $tokens = self::findTokenBySelector($selector);
            if ($tokens && password_verify($validator, $tokens['hashed_validator'])) {
                $user = Auth::findBySelector($selector);
                if ($user) {
                    Factory::instance()->getAuthController()->getStorage()->write($user->username);
                    return $user;
                }
            }
        }
        return null;
    }

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
    public static function insertToken(int $auth_id, string $selector, string $hashed_validator, string $expiry): int|bool
    {
        $browser_id = Factory::instance()->getCookie()->getBrowserId();
        return Db::insert('auth_remember', compact('auth_id', 'browser_id', 'selector', 'hashed_validator', 'expiry'));
    }

    /**
     * Find a row in the user_remember table by a selector.
     * It only returns the match selector if the token is not expired
     *   by comparing the expiry with the current time
     */
    public static function findTokenBySelector(string $selector): array
    {
        $browser_id = Factory::instance()->getCookie()->getBrowserId();
        $sql = 'SELECT id, selector, hashed_validator, browser_id, auth_id, expiry
            FROM auth_remember
            WHERE selector = :selector
            AND browser_id = :browser_id
            AND expiry >= NOW()
            LIMIT 1';
        return (array)Db::queryOne($sql, compact('selector', 'browser_id'));
    }

    public static function findTokenByAuthId(string $auth_id): array
    {
        $browser_id = Factory::instance()->getCookie()->getBrowserId();
        $sql = 'SELECT id, selector, hashed_validator, auth_id, expiry
            FROM auth_remember
            WHERE auth_id = :auth_id
            AND browser_id = :browser_id
            AND expiry >= NOW()
            LIMIT 1';
        return (array)Db::queryOne($sql, compact('auth_id', 'browser_id'));
    }

    public static function deleteToken(int $auth_id): bool|int
    {
        $browser_id = Factory::instance()->getCookie()->getBrowserId();
        return Db::delete('auth_remember', compact('auth_id', 'browser_id'));
    }

}