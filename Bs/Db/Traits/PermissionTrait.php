<?php
namespace Bs\Db\Traits;



/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2019 Michael Mifsud
 */
trait PermissionTrait
{
    /**
     * @return int
     */
    abstract public function getId();
    /**
     * @return bool
     */
    abstract public function isActive();
    /**
     * @return \Tk\Db\Map\Mapper
     */
    abstract public function getMapper();



    /**
     * @return array
     */
    public function getPermissions()
    {
        try {
            return $this->getMapper()->getPermissions($this->getId());
        } catch (\Exception $e) {}
        return array();
    }

    /**
     * @param string $name
     * @return $this
     */
    public function addPermission($name)
    {
        try {
            $this->getMapper()->addPermission($this->getId(), $name);
        } catch (\Exception $e) {
        }
        return $this;
    }

    /**
     * @param string $name (optional) If omitted then all permissions are removed
     * @return $this
     */
    public function removePermission($name = null)
    {
        try {
            $this->getMapper()->removePermission($this->getId(), $name);
        } catch (\Exception $e) {
        }
        return $this;
    }

    /**
     * Check if this object has the requested permission
     *
     * @param string|string[] $permission
     * @return bool
     */
    public function hasPermission($permission)
    {
        if (!$this->isActive()) return false;
        if (!is_array($permission)) $permission = array($permission);
        foreach ($permission as $p) {
            try {
                if ($this->getMapper()->hasPermission($this->getId(), $p))
                    return true;
            } catch (\Exception $e) {}
        }
        return false;
    }


}
