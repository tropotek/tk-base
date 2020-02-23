<?php
namespace Bs\Db\Traits;



use Exception;
use Tk\Db\Map\Mapper;

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
     * @return Mapper
     */
    abstract public function getMapper();


    /**
     * @return array
     */
    public function getPermissions()
    {
        try {
            return $this->getMapper()->getPermissions($this->getVolatileId());
        } catch (Exception $e) {}
        return array();
    }

    /**
     * @param string|array $name
     * @return $this
     */
    public function addPermission($name)
    {
        try {
            if (!is_array($name)) $name = array($name);
            foreach ($name as $item) {
                $this->getMapper()->addPermission($this->getVolatileId(), $item);
            }
        } catch (Exception $e) {}
        return $this;
    }

    /**
     * @param string $name (optional) If omitted then all permissions are removed
     * @return $this
     */
    public function removePermission($name = null)
    {
        try {
            $this->getMapper()->removePermission($this->getVolatileId(), $name);
        } catch (Exception $e) {}
        return $this;
    }

    /**
     * Check if this object has the requested permission
     *
     * @param string|string[]|array $permission
     * @return bool
     */
    public function hasPermission($permission)
    {
        if (!$this->isActive()) return false;
        if (!is_array($permission)) $permission = array($permission);
        foreach ($permission as $p) {
            try {
                    if ($this->getMapper()->hasPermission($this->getVolatileId(), $p))
                    return true;
            } catch (Exception $e) {}
        }
        return false;
    }


}
