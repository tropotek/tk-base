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

    // TODO: We need to deprecate these constants as they are influencing the app design
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
     * @todo We need to remove the reliance on these constants as it influences class inheritance
     * @deprecated removing roleType over time
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
     * @param string $name
     * @param array $arguments
     * @return bool|mixed
     * @throws \Tk\Exception
     * @todo: not sure if this is good design, feels like it will come back to haunt us see is()
     */
    public function __call($name, $arguments)
    {
        // Allow us to do isStaff, isStudent, etc and use the name of the role as the comparitor
        if (preg_match('/^is([a-zA-Z0-9_]+)/', $name, $regs)) {
            $roleName = strtolower($regs[1]);
            return $this->is($roleName);
        }
        throw new \Tk\Exception('Method does not exist: ' . $name . '()');
    }

    /**
     * Case insensitive check of the roleName.
     * An array of names can be supplied if one matches then true is returned.
     *
     * @param string|array $roleName
     * @return bool
     */
    public function is($roleName)
    {
        if (!is_array($roleName)) $roleName = array($roleName);
        foreach ($roleName as $r) {
            if (strtolower($this->name) == strtolower($r))
                return true;
        }
        return false;
    }

    /**
     * @param string|array $type
     * @return bool
     * @deprecated removing roleType over time
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
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     * @deprecated removing roleType over time
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
     * @param string|string[] $name
     * @return bool
     */
    public function hasPermission($name)
    {
        if (!$this->isActive()) return false;
        if (!is_array($name)) $name = array($name);
        foreach ($name as $p) {
            if ($this->getMapper()->hasPermission($this->getId(), $p))
                return true;
        }
        return false;
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
