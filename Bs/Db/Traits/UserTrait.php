<?php
namespace Bs\Db\Traits;

use Bs\Db\User;
use Bs\Db\UserInterface;
use Bs\Config;


/**
 * @author Michael Mifsud <http://www.tropotek.com/>
 * @link http://www.tropotek.com/
 * @license Copyright 2019 Michael Mifsud
 */
trait UserTrait
{

    /**
     * @var UserInterface
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
     * @param int|UserInterface $userId
     * @return UserTrait
     */
    public function setUserId($userId)
    {
        if ($userId instanceof UserInterface) $userId = $userId->getId();
        $this->userId = (int)$userId;
        return $this;
    }

    /**
     * Find this institutions owner user
     *
     * @return UserInterface|\Uni\Db\UserIface|User|\Uni\Db\User|null
     * @throws \Exception
     */
    public function getUser()
    {
        if (!$this->_user)
            $this->_user = Config::getInstance()->getUserMapper()->find($this->getUserId());
        return $this->_user;
    }

    /**
     * Set the author of this notice
     *
     * @param int|UserInterface $user
     * @return $this
     * @deprecated Use set UserId(UserIface)
     */
    public function setUser($user)
    {
        \Tk\Log::warning('Using Deprecated Method UserTrait::setUser()');
        if ($user instanceof UserInterface) {
            $this->user = $user;
            $this->userId = $user->getId();
        }
        return $this;
    }

    /**
     * Find this institutions owner user
     *
     * Note: This is use as an alias incases where get{Object}()
     *   is already used in the main object for another reason
     *
     * @return UserInterface|null
     * @throws \Exception
     * @deprecated Use getUser()
     */
    public function getUserObj()
    {
        return $this->getUser();
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
