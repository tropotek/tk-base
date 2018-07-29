<?php
namespace Bs;

/**
 * @author Tropotek <info@tropotek.com>
 * @created: 22/07/18
 * @link http://www.tropotek.com/
 * @license Copyright 2018 Tropotek
 */
class FormIface extends \Tk\Form
{
    /**
     * @var null|\Tk\Db\ModelInterface
     */
    protected $model = null;

    /**
     * Set to true on first call to initFields()
     * @var bool
     */
    private $initDone = false;

    /**
     * @param $formId
     * @param string $method
     * @param string|\Tk\Uri|null $action
     * @return FormIface|\Tk\Form|static
     */
    public static function create($formId, $method = self::METHOD_POST, $action = null)
    {
        /** @var FormIface $obj */
        $obj = parent::create($formId, $method, $action);
        $obj->setRenderer(\Bs\Config::getInstance()->createFormRenderer($obj));
        return $obj;
    }

    /**
     * @return null|\Tk\Db\ModelInterface
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @param null|\Tk\Db\ModelInterface $model
     * @return static
     */
    public function setModel($model)
    {
        $this->model = $model;
        if (!$this->initDone) {
            $this->initFields();
            $this->initDone = true;
        }
        return $this;
    }

    /**
     * Useful for extended form objects
     */
    public function initFields() { }



    /**
     * @return Config
     */
    public function getConfig()
    {
        return \Bs\Config::getInstance();
    }

    /**
     * @return \Tk\Uri
     * @throws \Exception
     */
    public function getBackUrl()
    {
        return $this->getConfig()->getBackUrl();
    }

    /**
     * @return Db\User
     */
    public function getUser()
    {
        return $this->getConfig()->getUser();
    }


}