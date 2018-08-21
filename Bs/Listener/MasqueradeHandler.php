<?php
namespace Bs\Listener;

use Tk\Event\Subscriber;
use Tk\Kernel\KernelEvents;
use Tk\Event\GetResponseEvent;
use Bs\Db\User;
use Bs\Db\Role;
use Tk\Event\AuthEvent;
use Tk\Auth\AuthEvents;

/**
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class MasqueradeHandler implements Subscriber
{
    /**
     * Session ID
     */
    const SID = '__masquerade__';

    /**
     * The query string for the msq user
     * Eg: `index.html?msq=23`
     */
    const MSQ = 'msq';

    /**
     * The order of role permissions
     * @var array
     */
    public static $roleOrder = array(
        Role::TYPE_ADMIN,        // Highest
        Role::TYPE_USER          // Lowest
    );

    /**
     * Add any headers to the final response.
     *
     * @param GetResponseEvent $event
     */
    public function onMasquerade(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $config = \Bs\Config::getInstance();
        if (!$request->has(self::MSQ)) return;

        try {
            /** @var User $user */
            $user = $config->getUser();
            if (!$user) throw new \Tk\Exception('Invalid User');
            /** @var User $msqUser */
            $msqUser = $config->getUserMapper()->findByHash($request->get(self::MSQ));
            if (!$msqUser) throw new \Tk\Exception('Invalid User');
            self::masqueradeLogin($user, $msqUser);
        } catch (\Exception $e) {
            \Tk\Alert::addWarning($e->getMessage());
        }
    }


    // -------------------  Masquerade functions  -------------------

    /**
     * Check if this user can masquerade as the supplied msqUser
     *
     * @param User $user
     * @param User $msqUser
     * @return bool
     */
    public static function canMasqueradeAs($user, $msqUser)
    {
        if (!$msqUser || !$user || !$msqUser->active) return false;
        if ($user->getId() == $msqUser->getId()) return false;

        $config = \Bs\Config::getInstance();
        $msqArr = $config->getSession()->get(self::SID);
        if (is_array($msqArr)) {    // Check if we are already masquerading as this user in the queue
            foreach ($msqArr as $data) {
                if ($data['userId'] == $msqUser->id) return false;
            }
        }

        // Admins can masquerade as anyone except themselves
        if ($user->isAdmin()) {
            return true;
        }

        // If not admin their role must be higher in precedence see User::$roleOrder
        $msqRoleIdx = array_search($msqUser->getRoleType(), self::$roleOrder);
        $userRoleIdx = array_search($user->getRoleType(), self::$roleOrder);
        if ($userRoleIdx >= $msqRoleIdx) {
            return false;
        }
        return true;
    }


    /**
     * If this user is masquerading
     *
     * 0 if not masquerading
     * >0 The masquerading total (for nested masquerading)
     *
     * @return int
     */
    public static function isMasquerading()
    {
        $config = \Bs\Config::getInstance();

        if (!$config->getSession()->has(self::SID)) return 0;
        $msqArr = $config->getSession()->get(self::SID);
        return count($msqArr);
    }

    /**
     *
     * @param User $user
     * @param User $msqUser
     * @return bool|void
     * @throws \Exception
     */
    public static function masqueradeLogin($user, $msqUser)
    {
        if (!$msqUser || !$user) return;
        if ($user->id == $msqUser->id) return;

        $config = \Bs\Config::getInstance();
        // Get the masquerade queue from the session
        $msqArr = $config->getSession()->get(self::SID);
        if (!is_array($msqArr)) $msqArr = array();

        if (!self::canMasqueradeAs($user, $msqUser)) {
            return;
        }

        // Save the current user and url to the session, to allow logout
        $userData = array(
            'userId' => $user->id,
            'url' => \Tk\Uri::create()->remove(self::MSQ)->toString()
        );
        array_push($msqArr, $userData);
        // Save the updated masquerade queue
        $config->getSession()->set(self::SID, $msqArr);

        // Login as the selected user
        $config->getAuth()->getStorage()->write($msqUser->getUsername());
        $config->getUserHomeUrl($msqUser)->redirect();
    }

    /**
     * masqueradeLogout
     *
     * @throws \Exception
     */
    public static function masqueradeLogout()
    {
        $config = \Bs\Config::getInstance();
        if (!self::isMasquerading()) return;
        if (!$config->getAuth()->hasIdentity()) return;
        $msqArr = $config->getSession()->get(self::SID);
        if (!is_array($msqArr) || !count($msqArr)) return;

        $userData = array_pop($msqArr);
        if (empty($userData['userId']) || empty($userData['url']))
            throw new \Tk\Exception('Session data corrupt. Clear session data and try again.');

        // Save the updated masquerade queue
        $config->getSession()->set(self::SID, $msqArr);

        /** @var User $user */
        $user = $config->getUserMapper()->find($userData['userId']);
        $config->getAuth()->getStorage()->write($user->username);

        \Tk\Uri::create($userData['url'])->redirect();
    }

    /**
     * masqueradeLogout
     */
    public static function masqueradeClear()
    {
        \Bs\Config::getInstance()->getSession()->remove(self::SID);
    }

    /**
     * @param AuthEvent $event
     * @throws \Exception
     */
    public function onLogout(AuthEvent $event)
    {
        if (self::isMasquerading()) {   // stop masquerading
            self::masqueradeLogout();
        }
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => 'onMasquerade',
            AuthEvents::LOGOUT => array('onLogout', 10)
        );
    }
}