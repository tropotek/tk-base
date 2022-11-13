<?php
namespace Bs;


/**
 * @author Tropotek <http://www.tropotek.com/>
 * @created: 22/07/18
 * @link http://www.tropotek.com/
 * @license Copyright 2018 Tropotek
 */
abstract class FormInterface extends \Tk\Form
{

    /**
     * @var null|\Tk\Db\ModelInterface|\Tk\Collection
     */
    protected $model = null;

    /**
     * Set to true on first call to initFields()
     * @var bool
     */
    private $initDone = false;


    /**
     * @param string $formId
     */
    public function __construct($formId = '')
    {
        if (!$formId)
            $formId = trim(strtolower(preg_replace('/[A-Z]/', '_$0', \Tk\ObjectUtil::basename(get_class($this)))), '_');
        parent::__construct($formId);
    }

    /**
     * @param string $formId
     * @return FormInterface|\Tk\Form|static
     */
    public static function create($formId = '')
    {
        /** @var FormInterface $obj */
        $obj = parent::create($formId);
        $obj->setDispatcher($obj->getConfig()->getEventDispatcher());
        $obj->setRenderer($obj->getConfig()->createFormRenderer($obj));
        return $obj;
    }

    /**
     * @param null|\Tk\Db\ModelInterface $model
     * @return FormInterface|\Tk\Form|static
     */
    public static function createModel($model = null)
    {
        /** @var FormInterface $obj */
        $obj = self::create(\Tk\ObjectUtil::basename($model));
        $obj->setModel($model);
        return $obj;
    }


    /**
     * @param \Tk\Request $request
     * @throws \Exception
     */
    public function execute($request = null)
    {
        parent::execute($request);
    }

    /**
     * @return null|\Tk\Db\ModelInterface|\Tk\Collection
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @param null|\Tk\Db\ModelInterface|\Tk\Collection $model
     * @return static
     */
    public function setModel($model)
    {
        $this->model = $model;
        if (!$this->initDone) {
            $this->init();  // Not sure this is correct, look into init() being called in the final instance???
            $this->initDone = true;
        }
        return $this;
    }

    /**
     * init all your form fields here
     */
    abstract public function init();

}