<?php
namespace Bs\Db\Traits;

use Bs\Db\User;
use Bs\Factory;

trait UserTrait
{

    private ?User $_user = null;


    public function getUser(): ?User
    {
        if (!$this->_user) {
            $this->_user = User::find($this->userId);
        }
        return $this->_user;
    }

    public function setUser(User $user): static
    {
        $this->userId = $user->userId;
        $this->_user = $user;
        return $this;
    }

}
