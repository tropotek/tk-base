<?php
namespace Bs\Db\Traits;

use Bs\Db\User;
use Bs\Factory;

trait UserTrait
{

    private ?User $_user = null;


    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId)
    {
        $this->userId = $userId;
        return $this;
    }

    public function getUser(): ?User
    {
        if (!$this->_user) {
            $this->_user = Factory::instance()->getUserMap()->find($this->getUserId());
        }
        return $this->_user;
    }

    public function setUser(User $user): static
    {
        $this->_user = $user;
        $this->setUserId($user->getId());
        return $this;
    }

}
