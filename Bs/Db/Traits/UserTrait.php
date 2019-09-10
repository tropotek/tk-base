<?php
namespace Bs\Db\Traits;

use Bs\Db\UserIface;
use Bs\Config;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2019 Michael Mifsud
 */
trait UserTrait
{

    /**
     * @var UserIface
     */
    private $_user = null;


    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return UserTrait
     */
    public function setUserId($userId)
    {
        $this->userId = (int)$userId;
        return $this;
    }

    /**
     * Find this institutions owner user
     *
     * @return UserIface|null
     * @throws \Exception
     */
    public function getUser()
    {
        return $this->getUserObj();
    }

    /**
     * Set the author of this notice
     *
     * @param int|UserIface $user
     * @return $this
     */
    public function setUser($user)
    {
        if ($user instanceof UserIface) $user = $user->getId();
        $this->userId = (int)$user;
        return $this;
    }

    /**
     * Find this institutions owner user
     *
     * Note: This is use as an alias incases where get{Object}()
     *   is already used in the main object for another reason
     *
     * @return UserIface|null
     * @throws \Exception
     */
    public function getUserObj()
    {
        if (!$this->_user)
            $this->_user = Config::getInstance()->getUserMapper()->find($this->getUserId());
        return $this->_user;
    }

    /**
     * @param array $errors
     * @return array
     */
    public function validateUserId($errors = [])
    {
        if (!$this->getUserId()) {
            $errors['userId'] = 'Invalid value: userId';
        }
        return $errors;
    }


}