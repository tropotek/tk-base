<?php
namespace Bs\Controller\Admin\User;

use Tk\Request;
use Dom\Template;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Edit extends \Bs\Controller\AdminEditIface
{
    /**
     * Setup the controller to work with users of this role
     * @var string
     */
    protected $targetRole = 'user';

    /**
     * @var \Bs\Db\User
     */
    protected $user = null;


    /**
     * @throws \Exception
     */
    public function __construct()
    {
        $this->setPageTitle('User Edit');
    }

    /**
     * @param \Tk\Request $request
     * @param string $targetRole
     * @throws \Exception
     */
    public function doDefaultRole(\Tk\Request $request, $targetRole)
    {
        $this->targetRole = $targetRole;
        switch($targetRole) {
            case \Bs\Db\Role::TYPE_ADMIN:
                $this->setPageTitle('Admin Edit');
                break;
        }
        $this->doDefault($request);
    }

    /**
     * @param \Tk\Request $request
     * @throws \Exception
     */
    public function doDefault(\Tk\Request $request)
    {
        $this->user = $this->getConfig()->createUser();
        $this->user->roleId = \Bs\Db\Role::DEFAULT_TYPE_USER;
        if ($request->get('userId')) {
            $this->user = $this->getConfig()->getUserMapper()->find($request->get('userId'));
        }

        $this->setForm(\Bs\Form\User::create()->setModel($this->user));
        $this->initForm($request);
        $this->getForm()->execute($request);
    }

    /**
     *
     */
    public function initActionPanel()
    {
        if ($this->user->getId() && $this->getConfig()->getMasqueradeHandler()->canMasqueradeAs($this->getUser(), $this->user)) {
            $this->getActionPanel()->append(\Tk\Ui\Link::createBtn('Masquerade',
                \Bs\Uri::create()->reset()->set(\Bs\Listener\MasqueradeHandler::MSQ, $this->user->getHash()), 'fa fa-user-secret'))
                ->setAttr('data-confirm', 'You are about to masquerade as the selected user?')->addCss('tk-masquerade');
        }
    }

    /**
     * @return \Dom\Template
     * @throws \Exception
     */
    public function show()
    {
        $this->initActionPanel();
        $template = parent::show();
        
        // Render the form
        $template->appendTemplate('form', $this->form->show());
        
        if ($this->user->id)
            $template->setAttr('form', 'data-panel-title', $this->user->name . ' - [ID ' . $this->user->id . ']');
        
        return $template;
    }


    /**
     * DomTemplate magic method
     *
     * @return Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<HTML
<div class="tk-panel" data-panel-icon="fa fa-user" var="form"></div>
HTML;

        return \Dom\Loader::load($xhtml);
    }

}