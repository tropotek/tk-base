<?php
namespace Bs\Db;

use Tk\Db\Map\Model;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Role extends Model implements \Tk\ValidInterface, RoleIface
{

    const DEFAULT_TYPE_ADMIN = 1;
    const DEFAULT_TYPE_USER = 2;

    const TYPE_ADMIN    = 'admin';
    const TYPE_USER     = 'user';
    const TYPE_PUBLIC   = 'public';

    /**
     * @var int
     */
    public $id = 0;

    /**
     * @var string
     */
    public $name = '';

    /**
     * @var string
     */
    public $type = self::TYPE_PUBLIC;

    /**
     * @var string
     */
    public $description = '';

    /**
     * @var bool
     */
    public $active = true;

    /**
     * @var bool
     */
    public $static = false;

    /**
     * @var \DateTime
     */
    public $modified = null;

    /**
     * @var \DateTime
     */
    public $created = null;


    /**
     * Role constructor.
     */
    public function __construct()
    {
        $this->modified = new \DateTime();
        $this->created = new \DateTime();
    }

    /**
     * Get a default ole ID from a Type
     *
     * @param string $type Use the constants self::TYPE_ADMIN|self:TYPE_USER
     * @return int
     */
    public static function getDefaultRoleId($type)
    {
        switch($type) {
            case self::TYPE_ADMIN:
                return self::DEFAULT_TYPE_ADMIN;
            case self::TYPE_USER:
                return self::DEFAULT_TYPE_USER;
        }
        return 0;
    }

    /**
     * @return \Tk\Db\Map\Mapper|RoleMap
     */
    public function getMapper()
    {
        return self::createMapper();
    }

    public function save()
    {
        if (!$this->isStatic())
            parent::save();
    }

    public function update()
    {
        if (!$this->isStatic())
            return parent::update();
        return 0;
    }

    public function delete()
    {
        if (!$this->isStatic())
            return parent::delete();
        return 0;
    }

    /**
     * @param string|array $type
     * @return bool
     */
    public function hasType($type)
    {
        if (!is_array($type)) $type = array($type);
        foreach ($type as $r) {
            if ($r == $this->getType()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * If true this role is readonly
     * @return bool
     */
    public function isStatic()
    {
        return $this->static;
    }

    /**
     * @return array
     */
    public function getPermissions()
    {
        return $this->getMapper()->getPermissions($this->getId());
    }

    /**
     * @param string $name
     * @return RoleIface
     */
    public function addPermission($name)
    {
        $this->getMapper()->addPermission($this->getId(), $name);
        return $this;
    }

    /**
     * @param string $name (optional) If omitted then all permissions are removed
     * @return RoleIface
     */
    public function removePermission($name = null)
    {
        $this->getMapper()->removePermission($this->getId(), $name);
        return $this;
    }

    /**
     * Note: Be sure to check the active status of this role
     *       and return false if this is a non active role.
     *
     * @param string $name
     * @return bool
     */
    public function hasPermission($name)
    {
        if (!$this->isActive()) return false;
        return $this->getMapper()->hasPermission($this->getid(), $name);
    }

    /**
     * Validate this object's current state and return an array
     * with error messages. This will be useful for validating
     * objects for use within forms.
     *
     * @return array
     * @throws \Exception
     */
    public function validate()
    {
        $errors = array();

        if (!$this->name) {
            $errors['name'] = 'Invalid name value';
        }

        if (!$this->type) {
            $errors['type'] = 'Invalid type value';
        }

        return $errors;
    }
}
