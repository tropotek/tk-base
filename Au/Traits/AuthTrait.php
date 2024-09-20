<?php
namespace Au\Db\Traits;

use Au\Auth;

trait AuthTrait
{
    private ?Auth $_auth = null;

    public function getAuth(): ?Auth
    {
        if (!$this->_auth) {
            $this->_auth = Auth::findByModel($this);
        }
        return $this->_auth;
    }

}
