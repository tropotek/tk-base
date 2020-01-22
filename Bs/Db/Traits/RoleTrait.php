<?php
namespace Bs\Db\Traits;

use Bs\Db\RoleIface;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2019 Michael Mifsud
 */
trait RoleTrait
{

    /**
     * @var RoleIface
     */
    private $_role = null;


    /**
     * @return int
     */
    public function getRoleId()
    {
        return $this->roleId;
    }

    /**
     * @param int|RoleIface $roleId
     * @return $this
     */
    public function setRoleId($roleId)
    {
        if ($roleId instanceof RoleIface) $roleId = $roleId->getId();
        $this->roleId = (int)$roleId;
        return $this;
    }

    /**
     * Find this institutions owner role
     *
     * @return RoleIface|null
     */
    public function getRole()
    {
        if (!$this->_role) {
            try {
                $this->_role = $this->getConfig()->getRoleMapper()->find($this->getRoleId());
            } catch (\Exception $e) {
                \Tk\Log::warning('No valid role found, creating Public role ');
                $this->_role = $this->getConfig()->createRole();
            }
            //$this->_role = Config::getInstance()->getRoleMapper()->find($this->getRoleId());
        }
        return $this->_role;
    }

    /**
     * @param array $errors
     * @return array
     */
    public function validateRoleId($errors = [])
    {
        if (!$this->getRoleId()) {
            $errors['roleId'] = 'Invalid value: roleId';
        }
        return $errors;
    }


}