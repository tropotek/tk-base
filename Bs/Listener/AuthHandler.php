<?php
namespace Bs\Listener;

use Tk\Event\Subscriber;
use Tk\Kernel\KernelEvents;
use Tk\Event\ControllerEvent;
use Tk\Event\GetResponseEvent;
use Tk\Event\AuthEvent;
use Tk\Auth\AuthEvents;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class AuthHandler implements Subscriber
{

    /**
     * do any auth init setup
     *
     * @param GetResponseEvent $event
     * @throws \Tk\Db\Exception
     * @throws \Exception
     */
    public function onRequest(GetResponseEvent $event)
    {
        // if a user is in the session add them to the global config
        // Only the identity details should be in the auth session not the full user object, to save space and be secure.
        $config = \Bs\Config::getInstance();
        $auth = $config->getAuth();
        $user = null;                       // public user

        if ($auth->getIdentity()) {         // Check if user is logged in
            $user = $config->getUserMapper()->findByUsername($auth->getIdentity());
            $config->setUser($user);
            if (!$user->isActive()) {
                $config->setUser(null);
                $user = null;
                $config->getSession()->destroy();
            }
        }

        // ---------------- deprecated  ---------------------
        // The following is deprecated in preference of the validatePageAccess() method below
        $role = $event->getRequest()->getAttribute('role');
        // no role means page is publicly accessible
        if (!$role || empty($role)) return;
        if ($user) {
            if (!$user->getRole()->hasType($role)) {
                // Could redirect to a authentication error page.
                \Tk\Alert::addWarning('You do not have access to the requested page.');
                $config->getUserHomeUrl($user)->redirect();
            }
        } else {
            $this->getLoginUrl()->redirect();
        }
        //-----------------------------------------------------
    }


    /**
     * Use path for permission validation
     *
     * @param GetResponseEvent $event
     * @throws \Exception
     */
    public function validatePageAccess(GetResponseEvent $event)
    {
        $config = \Bs\Config::getInstance();

        // --------------------------------------------------------
        // Deprecated remove when role is no longer used as a route attribute
        $role = $event->getRequest()->getAttribute('role');
        if ($role) {
            \Tk\Log::notice('Using legacy page permission system');
            return;
        }
        // --------------------------------------------------------

        $urlRole = \Bs\Uri::create()->getRoleType($config->getAvailableUserRoleTypes());
        if ($urlRole && !$urlRole != 'public') {
            $role = '';
            if ($config->getUser()) {
                $role = $config->getUser()->getRole()->getType();
            }
            if (!$config->getUser()) {  // if no user and the url has permissions set
                $this->getLoginUrl()->redirect();
            }
            if ($role != $urlRole) {   // Finally check if the use has access to the url
                \Tk\Alert::addWarning('You do not have access to the requested page.');
                $config->getUserHomeUrl($config->getUser())->redirect();
            }
        }
    }

    /**
     * @param AuthEvent $event
     * @throws \Exception
     */
    public function onLogin(AuthEvent $event)
    {
        $config = \Bs\Config::getInstance();
        $auth = $config->getAuth();

        $result = null;
        if (!$event->getAdapter()) {
            $adapterList = $config->get('system.auth.adapters');
            foreach ($adapterList as $name => $class) {
                $event->setAdapter($config->getAuthAdapter($class, $event->all()));
                if (!$event->getAdapter()) continue;
                $result = $auth->authenticate($event->getAdapter());
                $event->setResult($result);
                if ($result && $result->isValid()) {
                    break;
                }
            }
        }
    }

    /**
     * @param AuthEvent $event
     * @throws \Exception
     */
    public function onLoginSuccess(AuthEvent $event)
    {
        $config = \Bs\Config::getInstance();
        $result = $event->getResult();
        if (!$result) {
            throw new \Tk\Auth\Exception('Invalid login credentials');
        }
        if (!$result->isValid()) {
            return;
        }

        /* @var \Bs\Db\User $user */
        $user = $config->getUserMapper()->findByUsername($result->getIdentity());
        if (!$user) {
            throw new \Tk\Auth\Exception('Invalid user login credentials');
        }
        if (!$user->isActive()) {
            throw new \Tk\Auth\Exception('Inactive account, please contact your administrator.');
        }

        if($user && $event->getRedirect() == null) {
            $event->setRedirect(\Bs\Config::getInstance()->getUserHomeUrl($user));
        }

        // Update the user record.
        $user->lastLogin = \Tk\Date::create();
        $user->save();

    }

    /**
     * @param AuthEvent $event
     * @throws \Exception
     */
    public function onLogout(AuthEvent $event)
    {
        $config = \Bs\Config::getInstance();
        $auth = $config->getAuth();
        $url = $event->getRedirect();
        if (!$url) {
            $event->setRedirect(\Tk\Uri::create('/'));
        }
        $auth->clearIdentity();
        //$config->getSession()->destroy();     // Screws with masquerading code
    }


    // TODO: For all emails lets try to bring it back to the default mail template
    // TODO:   make it configurable so we could add it back in the future????

    /**
     * @param \Tk\Event\Event $event
     * @throws \Exception
     */
    public function onRegister(\Tk\Event\Event $event)
    {
        /** @var \Bs\Db\User $user */
        $user = $event->get('user');
        $config = \Bs\Config::getInstance();

        $url = $this->getRegisterUrl()->set('h', $user->hash);

        $message = $config->createMessage();
        $content = sprintf('
    <h2>Account Registration.</h2>
    <p>
      Welcome {name}
    </p>
    <p>
      To complete your account registration please click on the following activation Link:<br/>
      <a href="{activate-url}">http://link-as-text/</a>
    </p>');
        $message->set('content', $content);
        $message->setSubject('Account Registration.');
        $message->addTo($user->email);
        $message->set('name', $user->name);
        $message->set('activate-url', $url->toString());
        \Bs\Config::getInstance()->getEmailGateway()->send($message);

    }

    /**
     * @param \Tk\Event\Event $event
     * @throws \Exception
     */
    public function onRegisterConfirm(\Tk\Event\Event $event)
    {
        /** @var \Bs\Db\User $user */
        $user = $event->get('user');
        $config = \Bs\Config::getInstance();

        // Send an email to confirm account active
        $url = $this->getLoginUrl();

        $message = $config->createMessage();
        $content = sprintf('
    <h2>Account Successfully Activated.</h2>
    <p>
      Welcome {name}
    </p>
    <p>
      Your account has been successfully activated click here to <a href="{login-url}">login</a>.
    </p>');
        $message->set('content', $content);
        $message->setSubject('Account Activation.');
        $message->addTo($user->email);
        $message->set('name', $user->name);
        $message->set('login-url', $url->toString());
        \Bs\Config::getInstance()->getEmailGateway()->send($message);

    }

    /**
     * @param \Tk\Event\Event $event
     * @throws \Exception
     */
    public function onRecover(\Tk\Event\Event $event)
    {
        /** @var \Bs\Db\User $user */
        $user = $event->get('user');
        $pass = $event->get('password');
        $config = \Bs\Config::getInstance();

        $url = $this->getLoginUrl();

        $message = $config->createMessage();
        $content = sprintf('
    <h2>Account Successfully Activated.</h2>
    <p>
      Welcome {name}
    </p>
    <p>
      Your account has been successfully activated click here to <a href="{login-url}">login</a>.
    </p>');
        $message->set('content', $content);
        $message->setSubject('Password Recovery');
        $message->addTo($user->email);
        $message->set('name', $user->name);
        $message->set('password', $pass);   // TODO: Find another way we cannot have teh password sent via email
        $message->set('login-url', $url->toString());       // TODO make this url link to the recover password page and they can create a new pass
        \Bs\Config::getInstance()->getEmailGateway()->send($message);

    }

    /**
     * @return \Bs\Uri
     */
    public function getLoginUrl()
    {
        return \Bs\Uri::create(\Bs\Config::getInstance()->get('url.auth.login'));
    }

    /**
     * @return \Bs\Uri
     */
    public function getRegisterUrl()
    {
        return \Bs\Uri::create(\Bs\Config::getInstance()->get('url.auth.register'));
    }




    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     *
     * @api
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => array(array('onRequest', 5), array('validatePageAccess', -5)),
            AuthEvents::LOGIN => 'onLogin',
            AuthEvents::LOGIN_SUCCESS => 'onLoginSuccess',
            AuthEvents::LOGOUT => 'onLogout',
            AuthEvents::REGISTER => 'onRegister',
            AuthEvents::REGISTER_CONFIRM => 'onRegisterConfirm',
            AuthEvents::RECOVER => 'onRecover'
        );
    }


}