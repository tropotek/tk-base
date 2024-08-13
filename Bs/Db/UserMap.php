<?php
namespace Bs\Db;

use Bs\Factory;
use Tk\DataMap\DataMap;
use Tk\Db\Mapper\Filter;
use Tk\Db\Mapper\Mapper;
use Tk\Db\Mapper\Result;
use Tk\Db\Tool;
use Tk\DataMap\Db;
use Tk\DataMap\Form;

/**
 * @deprecated To be removed
 */
class UserMap extends Mapper
{

    public function makeDataMaps(): void
    {

        if (!$this->getDataMappers()->has(self::DATA_MAP_DB)) {
            $map = new DataMap();
            $map->addDataType(new Db\Integer('userId', 'user_id'));
            $map->addDataType(new Db\Text('uid'));
            $map->addDataType(new Db\Text('type'));
            $map->addDataType(new Db\Integer('permissions'));
            $map->addDataType(new Db\Text('username'));
            $map->addDataType(new Db\Text('password'));
            $map->addDataType(new Db\Text('email'));
            $map->addDataType(new Db\Text('nameTitle', 'name_title'));
            $map->addDataType(new Db\Text('nameFirst', 'name_first'));
            $map->addDataType(new Db\Text('nameLast', 'name_last'));
            $map->addDataType(new Db\Text('nameDisplay', 'name_display'));
            $map->addDataType(new Db\Text('notes'));
            $map->addDataType(new Db\Text('timezone'));
            $map->addDataType(new Db\Boolean('active'));
            $map->addDataType(new Db\Text('sessionId', 'session_id'));
            $map->addDataType(new Db\Text('hash'));
            $map->addDataType(new Db\Date('lastLogin', 'last_login'));
            $map->addDataType(new Db\Date('modified'));
            $map->addDataType(new Db\Date('created'));
            $this->addDataMap(self::DATA_MAP_DB, $map);
        }

        if (!$this->getDataMappers()->has(self::DATA_MAP_FORM)) {
            $map = new DataMap();
            $map->addDataType(new Form\Text('userId'));
            $map->addDataType(new Form\Text('uid'));
            $map->addDataType(new Form\Text('type'));
            $map->addDataType(new Form\Integer('permissions'));
            $map->addDataType(new Form\Text('username'));
            $map->addDataType(new Form\Text('password'));
            $map->addDataType(new Form\Text('email'));
            $map->addDataType(new Form\Text('nameTitle'));
            $map->addDataType(new Form\Text('nameFirst'));
            $map->addDataType(new Form\Text('nameLast'));
            $map->addDataType(new Form\Text('nameDisplay'));
            $map->addDataType(new Form\Boolean('active'));
            $map->addDataType(new Form\Text('notes'));
            $map->addDataType(new Form\Text('timezone'));
            $this->addDataMap(self::DATA_MAP_FORM, $map);
        }
    }

    public function findByUsername(string $username): ?User
    {
        return $this->findFiltered(['username' => $username])->current();
    }

    public function findByEmail(string $email): ?User
    {
        return $this->findFiltered(['email' => $email])->current();
    }

    public function findBySelector(string $selector): ?User
    {
        return $this->findFiltered(['selector' => $selector])->current();
    }

    public function findByHash(string $hash): ?User
    {
        return $this->findFiltered(['hash' => $hash])->current();
    }

    /**
     * @return Result|User[]
     */
    public function findFiltered(array|Filter $filter, ?Tool $tool = null): Result
    {
        return $this->prepareFromFilter($this->makeQuery(Filter::create($filter)), $tool);
    }

    public function makeQuery(Filter $filter): Filter
    {
        $filter->appendFrom('%s a ', \Tt\Db::escapeTable($this->getTable()));

        if (!empty($filter['search'])) {
            $filter['search'] = '%' . $filter['search'] . '%';
            $w  = 'a.name_title LIKE :search OR ';
            $w  = 'a.name_first LIKE :search OR ';
            $w  = 'a.name_last LIKE :search OR ';
            $w  = 'a.name_display LIKE :search OR ';
            $w .= 'a.uid LIKE :search OR ';
            $w .= 'a.username LIKE :search OR ';
            $w .= 'a.email LIKE :search OR ';
            $w .= 'a.user_id LIKE :search OR ';
            if ($w) $filter->appendWhere('(%s) AND ', substr($w, 0, -3));
        }

        if (!empty($filter['id'])) {
            $filter['userId'] = $filter['id'];
        }
        if (!empty($filter['userId'])) {
            $filter->appendWhere('a.user_id IN %s AND ', $filter->getIn($filter['userId']));
            unset($filter['userId']);
        }

        if (!empty($filter['uid'])) {
            $filter->appendWhere('a.uid = :uid AND ');
        }

        if (!empty($filter['hash'])) {
            $filter->appendWhere('a.hash = :hash AND ');
        }

        if (!empty($filter['type'])) {
            $filter->appendWhere('a.type IN %s AND ', $filter->getIn($filter['type']));
            unset($filter['type']);
        }

        if (!empty($filter['username'])) {
            $filter->appendWhere('a.username = :username AND ');
        }

        if (!empty($filter['email'])) {
            $filter->appendWhere('a.email = :email AND ');
        }

        if (!$this->isEmpty($filter['active'] ?? null)) {
            $filter->appendWhere('a.active = :active AND ');
        }

        if (!empty($filter['exclude'])) {
            $filter->appendWhere('a.user_id NOT IN %s AND ', $filter->getIn($filter['exclude']));
            unset($filter['exclude']);
        }

        // Filter for any remember me saved token selectors
        if (!empty($filter['selector'])) {
            $filter->appendFrom('INNER JOIN user_remember z USING (user_id) ');
            $filter->appendWhere('z.selector = :selector AND expiry > NOW() AND ');
        }

        return $filter;
    }

    /*
     * Functions to manage the "remember me" tokens
     * https://www.phptutorial.net/php-tutorial/php-remember-me/
     */

    /**
     * Generate a pair of random tokens called selector and validator
     */
    public function generateToken(): array
    {
        $selector = bin2hex(random_bytes(16));
        $validator = bin2hex(random_bytes(32));
        return [$selector, $validator, $selector . ':' . $validator];
    }

    /**
     * Split a token stored in the cookie into selector and validator
     */
    public function parseToken(string $token): ?array
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
    public function insertToken(int $user_id, string $selector, string $hashed_validator, string $expiry): int|bool
    {
        $browser_id = Factory::instance()->getCookie()->getBrowserId();
        return \Tt\Db::insert('user_remember', compact('user_id', 'browser_id', 'selector', 'hashed_validator', 'expiry'));
    }

    /**
     * Find a row in the user_remember table by a selector.
     * It only returns the match selector if the token is not expired
     *   by comparing the expiry with the current time
     */
    public function findTokenBySelector(string $selector): array
    {
        $browser_id = Factory::instance()->getCookie()->getBrowserId();
        $sql = 'SELECT id, selector, hashed_validator, browser_id, user_id, expiry
            FROM user_remember
            WHERE selector = :selector
            AND browser_id = :browser_id
            AND expiry >= NOW()
            LIMIT 1';
        return (array)\Tt\Db::queryOne($sql, compact('selector', 'browser_id'));
    }

    public function findTokenByUserId(string $user_id): array
    {
        $browser_id = Factory::instance()->getCookie()->getBrowserId();
        $sql = 'SELECT id, selector, hashed_validator, user_id, expiry
            FROM user_remember
            WHERE user_id = :user_id
            AND browser_id = :browser_id
            AND expiry >= NOW()
            LIMIT 1';

        return (array)\Tt\Db::queryOne($sql, compact('user_id', 'browser_id'));
    }

    public function deleteToken(int $user_id): bool|int
    {
        $browser_id = Factory::instance()->getCookie()->getBrowserId();
        return \Tt\Db::delete('user_remember', compact('user_id', 'browser_id'));
    }
}
