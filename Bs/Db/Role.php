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


    use Traits\TimestampTrait;

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
        $this->_TimestampTrait();
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
            if (strtolower($this->getName()) == strtolower($r))
                return true;
        }
        return false;
    }

    /**
     * @param string|array $type
     * @return bool
     * @deprecated removing roleType over time Use hasPermission
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
     * @param string $name
     * @return Role
     */
    public function setName(string $name): Role
    {
        $this->name = $name;
        return $this;
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
     * @param string $type
     * @return Role
     */
    public function setType(string $type): Role
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return Role
     */
    public function setDescription(string $description): Role
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * @param bool $active
     * @return Role
     */
    public function setActive(bool $active): Role
    {
        $this->active = $active;
        return $this;
    }

    /**
     * If true this role is readonly
     * @return bool
     */
    public function isStatic(): bool
    {
        return $this->static;
    }

    /**
     * @param bool $static
     * @return Role
     */
    public function setStatic(bool $static): Role
    {
        $this->static = $static;
        return $this;
    }



    /**
     * @return array
     */
    public function getPermissions()
    {
        try {
            return $this->getMapper()->getPermissions($this->getId());
        } catch (\Exception $e) {}
        return false;
    }

    /**
     * @param string $name
     * @return RoleIface
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
     * @return RoleIface
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
     * Check if this Role has the requested permission
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
