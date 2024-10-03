<?php
namespace Bs\Traits;

use Bs\Auth;

trait AuthTrait
{
    private ?Auth $_auth = null;

    public function getAuth(): Auth
    {
        if (!$this->_auth) {
            $this->_auth = Auth::findByModel($this);
            if (!$this->_auth) {
                $this->_auth = Auth::create($this);
            }
        }
        return $this->_auth;
    }

}
