<?php
namespace Bs\Db;

use Tk\Db;

/**
 * Records failed login attempts for brute-force throttling.
 * Keyed by username + IP over a sliding time window.
 */
class LoginAttempt
{
    public static function record(string $username, string $ip): void
    {
        Db::execute(
            "INSERT INTO auth_login_attempt (username, ip) VALUES (:username, :ip)",
            compact('username', 'ip')
        );
    }

    public static function countRecent(string $username, string $ip, int $windowMins): int
    {
        self::prune($windowMins);

        $row = Db::queryOne(
            "SELECT COUNT(*) AS c FROM auth_login_attempt
             WHERE username = :username AND ip = :ip
               AND created >= (NOW() - INTERVAL :mins MINUTE)",
            ['username' => $username, 'ip' => $ip, 'mins' => $windowMins]
        );
        return (int)($row->c ?? 0);
    }

    public static function clear(string $username, string $ip): void
    {
        Db::execute(
            "DELETE FROM auth_login_attempt WHERE username = :username AND ip = :ip",
            compact('username', 'ip')
        );
    }

    /**
     * Delete rows older than the caller's own lookback window, since rows past
     * that point can never affect a countRecent() result again. Runs on every
     * countRecent() call so the table never grows unbounded without needing a
     * separate cron/GC job or a retention value independent of what callers
     * already configure.
     */
    protected static function prune(int $windowMins): void
    {
        Db::execute(
            "DELETE FROM auth_login_attempt WHERE created < (NOW() - INTERVAL :mins MINUTE)",
            ['mins' => $windowMins]
        );
    }
}
