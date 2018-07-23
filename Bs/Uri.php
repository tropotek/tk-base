<?php
namespace Bs;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2016 Michael Mifsud
 */
class Uri extends \Tk\Uri
{

    /**
     * A static factory method to facilitate inline calls
     *
     * <code>
     *   \Tk\Uri::create('http://example.com/test');
     * </code>
     *
     * @param null|string|\Tk\Uri $spec
     * @param null|\Bs\Db\User $user
     * @return string|\Tk\Uri|static
     */
    public static function createHomeUrl($spec = null, $user = null)
    {
        if ($spec instanceof \Tk\Uri)
            return clone $spec;

        $home = $user;
        if (!$user) {
            $user = Config::getInstance()->getUser();
        }
        if (is_object($user)) {
            $home = Config::getInstance()->getUserHomeUrl($user);
            if($home instanceof \Tk\Uri) {
                $home = $home->getRelativePath();
            }
            $home = dirname($home);
        }
        return new static($home . '/' . trim($spec,'/'));
    }

    /**
     * Call this to ensure the breadcrumb system ignores this URL
     *
     * @param bool $b
     * @return static
     */
    public function ignoreCrumb($b = true)
    {
        if ($b)
            $this->set(\Tk\Crumbs::CRUMB_IGNORE);
        else
            $this->remove(\Tk\Crumbs::CRUMB_IGNORE);
        return $this;
    }

    /**
     * Debug Only
     * Call this to enable/disable log entries for this url
     *
     * @param bool $b
     * @return static
     */
    public function noLog($b = true)
    {
        if ($b)
            $this->set('nolog');
        else
            $this->remove('nolog');
        return $this;
    }


    /**
     * Attempts to get a valid user role from the start of a site path
     *
     *
     * Example uses following roles array:
     *  $roles = array('admin', 'user');
     *
     * URI`s return values:
     *  o /index.html               => ''
     *  o /user/profile.html        => 'user'
     *  o /system/admin/edit.html   => ''
     *  o /admin/settings           => 'admin
     *
     * The script check for a role after the first `/` char and if exists returns that as the found role.
     * This call will be handy for authentication and page template loading
     *
     * @param array $roles  Supply a list of available roles to search for
     * @return string
     */
    public function getRole($roles = array())
    {
        if (preg_match('|^\/([a-z0-9_-]+).*|', $this->getRelativePath(), $regs)) {
            if (!empty($regs[1]) && in_array($regs[1], $roles)) {
                return $regs[1];
            }
        }
        return '';
    }
}