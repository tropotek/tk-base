<?php
namespace Bs\Db;

use Au\Auth;
use Bs\Traits\SystemTrait;
use Tk\DataMap\DataMap;
use Tk\DataMap\Db\DateTime;
use Tk\DataMap\Db\Integer;
use Tk\DataMap\Db\Json;
use Tk\DataMap\Db\Text;
use Tk\Exception;
use Tk\Db;
use Tk\Db\Model;
use Tk\Uri;

class GuestToken extends Model
{
    use SystemTrait;

    // session ID
    const TOKEN_SID = '_guest_token';
    // request ID
    const TOKEN_RID = '__gt';

    public string     $token   = '';
    public array      $pages   = [];
    public array      $payload = [];
    public int        $ttlMins = 10;
    public ?\DateTime $created = null;
    public ?\DateTime $expiry = null;


    /**
     * create a custom data map
     */
    public static function getDataMap(): DataMap
    {
        $map = self::$_MAPS[static::class] ?? null;
        if (!is_null($map)) return $map;

        $map = new DataMap();
        //$map->addType(new Text('token'))->setFlag(DataMap::PRI);
        $map->addType(new Text('token'));
        $map->addType(new Json('pages'))->setAssociative(true);
        $map->addType(new Json('payload'))->setAssociative(true);
        $map->addType(new Integer('ttlMins', 'ttl_mins'));
        $map->addType(new DateTime('created'), DataMap::READ);
        $map->addType(new DateTime('expiry'), DataMap::READ);

        self::$_MAPS[static::class] = $map;
        return $map;
    }

    public static function create(array $pages, array $payload, int $ttlMins): static
    {
        if (!$pages) {
            throw new Exception('no pages available');
        }

        $obj = new static();
        $obj->pages = $pages;
        $obj->payload = $payload;
        $obj->ttlMins = $ttlMins;

        $map = static::getDataMap();
        $gt = (object)$map->getArray($obj);

		$ok = 0;
		while (!$ok) {
			$gt->token = hash('sha256', microtime() . random_bytes(256));
			$ok = DB::execute("
				INSERT INTO guest_token (token, pages, payload, ttl_mins)
				VALUES (:token, :pages, :payload, :ttl_mins)",
				$gt
			);
		}

		$token = $gt->token;
		$gt = self::find($token);
		assert(is_object($gt), "failed to get token {$token}");
        return $gt;
    }

    public function getUrl(): Uri
    {
        return Uri::create('/')->set(self::TOKEN_RID, $this->token);
    }

    public function hasUrl(string|Uri $url): bool
    {
        $url = Uri::create($url);
        foreach ($this->pages as $page) {
            $u = Uri::create($page);
            if ($url->getPath() == $u->getPath()) {
                return true;
            }
        }
        return false;
    }

	public function delete(): bool
	{
        Auth::logout();
        unset($_SESSION[GuestToken::TOKEN_SID]);

		return false !== DB::execute("
			DELETE from guest_token
			WHERE token = :token",
			$this
		);
	}

    public static function getSessionToken(): ?static
    {
        return GuestToken::find($_SESSION[GuestToken::TOKEN_SID] ?? '');
    }

    /**
     * @todo: test this, and see if we need it
     */
	public static function deleteByPage(string $page): bool
	{
		$page = trim($page);
        if (empty($page)) return false;

		return false !== DB::execute("
			DELETE FROM guest_token
			WHERE :page MEMBER OF(pages)",
			compact('page')
		);
	}

    public static function find(string $token): ?static
    {
        $token = trim($token);
        if (empty($token)) return null;

        return Db::queryOne("
            SELECT *
            FROM guest_token
            WHERE token = :token
            AND expiry >= NOW()",
            compact('token'),
            self::class
        );
    }

    public static function findAll(): array
    {
        return Db::query("
            SELECT *
            FROM guest_token
            WHERE expiry >= NOW()",
            [],
            self::class
        );
    }

    /**
     * convert number of hours to minutes
     */
	public static function hours_to_minutes(int $hours): int
	{
		return $hours * 60;
	}

    /**
     * convert number of days to minutes
     */
	public static function days_to_minutes(int $days): int
	{
		return $days * 60 * 24;
	}

    /**
     * convert a date to a number of minutes, with optional offset in days
     */
	public static function date_to_minutes(string $date, int $offset=0): int
	{
		$now = new \DateTime();
		$diff = $now->diff(new \DateTime($date));

		return self::days_to_minutes($diff->days + $offset);
	}

}