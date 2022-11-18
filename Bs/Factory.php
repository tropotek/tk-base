<?php
namespace Bs;

use Bs\Console\UserPass;
use Symfony\Component\Console\Application;
use Tk\Auth\Adapter\AdapterInterface;
use Tk\Auth\Adapter\DbTable;

/**
 * @author Tropotek <http://www.tropotek.com/>
 */
class Factory extends \Tk\Factory
{

    /**
     * @return Application
     */
    public function getConsole(): Application
    {
        if (!$this->has('console')) {
            $app = parent::getConsole();

            if ($this->getConfig()->isDebug()) {
                $app->add(new UserPass());
            }

            $this->set('console', $app);
        }
        return $this->get('console');
    }

    /**
     * This is the default Authentication adapter
     * Override this method in your own site's Factory object
     */
    public function getAuthAdapter(): AdapterInterface
    {
        if (!$this->has('authAdapter')) {
            $adapter = new DbTable($this->getDb(), 'user_auth', 'username', 'password');
            $this->set('authAdapter', $adapter);
        }
        return $this->get('authAdapter');
    }
}