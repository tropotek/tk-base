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
     * @param null|\Uni\Db\UserIface $user
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
            $this->set(\Uni\Ui\Crumbs::CRUMB_IGNORE);
        else
            $this->remove(\Uni\Ui\Crumbs::CRUMB_IGNORE);
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

}