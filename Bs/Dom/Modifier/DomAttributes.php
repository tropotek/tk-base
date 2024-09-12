<?php
namespace Bs\Dom\Modifier;

use Bs\Db\Permissions;
use Bs\Db\User;
use Bs\Factory;
use Dom\Modifier\FilterInterface;
use Tk\Config;

/**
 * This object checks for any app attribute tags in a template and modifies a node as
 * required by the app tag.
 * Available attributes available for nodes are:
 *
 * - app-is-type="User::TYPE_STAFF": remove a node if the current user is not of user type
 * - app-has-perm="User::PERM_SYSADMIN":  remove a node if the current user does not have the permissions
 *
 * @experimental
 * @todo Look further into this, I think we can deprecated it and prefer this at the App level
 * @deprecated This should be implemented on a per site level, not in a lib
 */
class DomAttributes extends FilterInterface
{

    const APP_IS_USER   = 'app-is-user';
    const APP_IS_TYPE   = 'app-is-type';
    const APP_HAS_PERM  = 'app-has-perm';
    const APP_IS_DEBUG  = 'app-is-debug';

    protected bool  $isUser        = false;
    protected ?User $authUser      = null;
    protected array $permissionCon = [];
    protected array $userCon       = [];


    public function __construct()
    {
        $this->isUser = is_object(Factory::instance()->getAuthUser());
        $this->authUser = Factory::instance()->getAuthUser();

        $reflect = new \ReflectionClass(User::$USER_CLASS);
        $this->userCon = $reflect->getConstants();

        $reflect = new \ReflectionClass(Permissions::class);
        $this->permissionCon = $reflect->getConstants();

    }

    /**
     * pre init the Filter
     */
    public function init(\DOMDocument $doc) { }

    /**
     * Call this method to traverse a document
     */
    public function executeNode(\DOMElement $node): void
    {
        $isDebug = Config::instance()->isDebug();

        try {

            if ($node->hasAttribute(self::APP_IS_DEBUG)) {
                $val = trim($node->getAttribute(self::APP_IS_DEBUG));
                $node->removeAttribute(self::APP_IS_DEBUG);
                $showNode = preg_match('/(yes|true|1)/i', $val);
                if (($isDebug && !$showNode) || (!$isDebug && $showNode)) {
                    $this->getDomModifier()->removeNode($node);
                }
            }

            if ($node->hasAttribute(self::APP_IS_USER)) {
                $val = trim($node->getAttribute(self::APP_IS_USER));
                $node->removeAttribute(self::APP_IS_USER);
                $showNode = preg_match('/(yes|true|1)/i', $val);
                if (($this->isUser && !$showNode) || (!$this->isUser && $showNode)) {
                    $this->getDomModifier()->removeNode($node);
                }
            }

            if ($node->hasAttribute(self::APP_IS_TYPE)) {
                $type = $node->getAttribute(self::APP_IS_TYPE);
                $node->removeAttribute(self::APP_IS_TYPE);
                if (!$this->authUser || !$this->authUser->isType($this->userCon[$type])) {
                    $this->getDomModifier()->removeNode($node);
                }
            }

            if ($node->hasAttribute(self::APP_HAS_PERM)) {
                $perms = explode('|', $node->getAttribute(self::APP_HAS_PERM));
                $node->removeAttribute(self::APP_HAS_PERM);
                $perms = array_map('trim', $perms);
                $perm = array_sum(array_filter($this->permissionCon,
                    function($k) use($perms) {
                        return in_array($k, $perms);
                    }, ARRAY_FILTER_USE_KEY)
                );

                if (!$this->authUser || !$this->authUser->hasPermission($perm)) {
                    $this->getDomModifier()->removeNode($node);
                }
            }

        } catch (\Exception $e) {}
    }

    /**
     * called after DOM tree is traversed
     */
    public function postTraverse(\DOMDocument $doc)
    {

    }
}
