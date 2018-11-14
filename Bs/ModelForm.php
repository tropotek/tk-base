<?php
namespace Bs;

use Dom\Renderer\Renderer;
use Dom\Template;

/**
 * @author Tropotek <info@tropotek.com>
 * @created: 22/07/18
 * @link http://www.tropotek.com/
 * @license Copyright 2018 Tropotek
 */
abstract class ModelForm extends \Dom\Renderer\Renderer implements \Dom\Renderer\DisplayInterface
{



    /**
     * @var \Tk\Form
     */
    protected $form = null;

    /**
     * @var null|\Tk\Db\ModelInterface
     */
    protected $model = null;


    /**
     * ModelForm constructor.
     * @param null|\Tk\Form $form
     */
    public function __construct($form = null)
    {
        $this->setForm($form);
    }

    /**
     * @param null|\Tk\Form $form
     * @return ModelForm|static
     */
    public static function create($form = null)
    {
        if (!$form) {
            $config = Config::getInstance();
            $formId = trim(strtolower(preg_replace('/[A-Z]/', '_$0', \Tk\ObjectUtil::basename(get_class(__CLASS__)))), '_');
            $form = $config->createForm($formId);
            $form->setRenderer($config->createFormRenderer($form));
        }
        $obj = new static($form);
        return $obj;
    }

    /**
     * @param \Tk\Db\ModelInterface $model
     * @param null|\Tk\Form $form
     * @return ModelForm|static
     */
    public static function createModel($model, $form = null)
    {
        if (!$form) {
            $config = Config::getInstance();
            $formId = \Tk\ObjectUtil::basename($model) . '_' . $model->getId();
            $form = $config->createForm($formId);
            $form->setRenderer($config->createFormRenderer($form));
        }
        $obj = self::create($form);
        $obj->setModel($model);
        return $obj;
    }


    /**
     * init all your form fields here
     */
    abstract public function init();

    /**
     * Execute the renderer.
     * Return an object that your framework can interpret and display.
     *
     * @return null|Template|Renderer
     */
    public function show()
    {
        $template = $this->getForm()->getRenderer()->show();

        return $template;
    }


    /**
     * @param \Tk\Request $request
     * @throws \Exception
     */
    public function execute($request = null)
    {
        $this->getForm()->execute($request);
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
        return $this;
    }

    /**
     * @return \Tk\Form
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * @param \Tk\Form $form
     * @return ModelForm
     */
    public function setForm($form)
    {
        $this->form = $form;
        return $this;
    }

    /**
     * @return null|\Tk\Form\Renderer\Iface
     */
    public function getRenderer()
    {
        return $this->getForm()->getRenderer();
    }

    /**
     * @return \Tk\Form\Renderer\Layout
     */
    public function getLayout()
    {
        return $this->getRenderer()->getLayout();
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return Config::getInstance();
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