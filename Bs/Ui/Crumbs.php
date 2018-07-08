<?php
namespace Bs\Ui;


/**
 * Use this object to track and render a crumb stack
 *
 * See the controlling object \Uni\Listeners\CrumbsHandler to
 * view its implementation.
 *
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2016 Michael Mifsud
 */
class Crumbs extends \Dom\Renderer\Renderer
{
    /**
     * Request param: Reset the crumb stack
     */
    const CRUMB_RESET = 'crumb_reset';
    /**
     * Request param: Do not add the current URI to the crumb stack
     */
    const CRUMB_IGNORE = 'crumb_ignore';

    /**
     * @var string
     */
    public static $homeUrl = '/index.html';

    /**
     * @var string
     */
    public static $homeTitle = 'Home';


    /**
     * @var Crumbs
     */
    public static $instance = null;

    /**
     * @var array
     */
    protected $list = array();

    /**
     * @var \Bs\Db\User
     */
    protected $user = null;

    /**
     * @var \Tk\Session
     */
    protected $session = null;


    /**
     * Crumbs constructor.
     * @param \Bs\Db\User $user
     * @param \Tk\Session $session
     */
    protected function __construct($user, $session)
    {
        $this->user = $user;
        $this->session = $session;
    }

    /**
     * @param \Bs\Db\User $user
     * @param \Tk\Session $session
     * @return static
     */
    static protected function create($user, $session)
    {
        $obj = new static($user, $session);
        return $obj;
    }

    /**
     * @param null|\Bs\Db\User $user
     * @param null|\Tk\Session $session
     * @return Crumbs
     */
    public static function getInstance($user = null, $session = null)
    {
        if (!$session)
            $session = \App\Config::getInstance()->getSession();
        if (!$user)
            $user = \App\Config::getInstance()->getUser();

        if (!self::$instance) {
            $crumbs = self::create($user, $session);
            if ($session->has($crumbs->getSid())) {
                $crumbs->setList($session->get($crumbs->getSid()));
            }
            if (!count($crumbs->getList())) {
                if ($crumbs->getUser())
                    $crumbs->addCrumb('Dashboard', $user->getHomeUrl());
                else
                    $crumbs->addCrumb(self::$homeTitle, \Tk\Uri::create(self::$homeUrl));
            }
            self::$instance = $crumbs;
        }
        return self::$instance;
    }

    /**
     * @param string $homeTitle
     * @param null|\Tk\Uri $url
     * @return null|Crumbs If null returned then the crumbs were not reset
     * @throws \Exception
     */
    public static function reset($homeTitle = 'Dashboard', $url = null)
    {
        $crumbs = self::getInstance();
        if ($crumbs && !\App\Config::getInstance()->getRequest()->has(self::CRUMB_IGNORE)) {
            if (!$url) {
                if ($crumbs->getUser()) {
                    $url = $crumbs->getUser()->getHomeUrl();
                } else {
                    $homeTitle = self::$homeTitle;
                    $url = \Tk\Uri::create(self::$homeUrl);
                }
            }
            $crumbs->getSession()->remove($crumbs->getSid());
            $crumbs->setList();
            $crumbs->addCrumb($homeTitle, $url);
            $crumbs->save();
            return $crumbs;
        }
    }


    /**
     * save the state of the crumb stack to the session
     */
    public static function save()
    {
        $crumbs = self::getInstance();
        if ($crumbs) {
            $crumbs->getSession()->set($crumbs->getSid(), $crumbs->getList());
        }
    }

    /**
     * Get the crumbs session ID
     *
     * @return string
     */
    public function getSid()
    {
        $uid = '000';
        if ($this->getUser())
            $uid = $this->getUser()->getId();
        return 'crumbs.' . $uid;
    }

    /**
     * @return \Bs\Db\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return \Tk\Session
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Get teh crumb list
     *
     * @return array
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * Use to restore crumb list.
     * format:
     *   array(
     *     'Page Name' => '/page/url/pageUrl.html'
     *   );
     *
     * @param $list
     */
    public function setList($list = array())
    {
        $this->list = $list;
    }

    /**
     * @return \Tk\Uri
     */
    public function getBackUrl()
    {
        $url = '';
        if (count($this->list) == 1) {
            $url = end($this->list);
        } if (count($this->list) > 1) {
        end($this->list);
        $url = prev($this->list);
    }
        return \Tk\Uri::create($url);
    }

    /**
     * @param string $title
     * @param \Tk\Uri|string $url
     * @return $this
     */
    public function addCrumb($title, $url)
    {
        $url = \Tk\Uri::create($url);
        $this->list[$title] = $url->toString();
        return $this;
    }

    /**
     * @param string $title
     * @param \Tk\Uri|string $url
     * @return $this
     */
    public function replaceCrumb($title, $url) {
        array_pop($this->list);
        return $this->addCrumb($title, $url);
    }

    /**
     * @param $title
     * @return array
     */
    public function trimByTitle($title) {
        $l = array();
        foreach ($this->list as $t => $u) {
            if ($title == $t) break;
            $l[$t] = $u;
        }
        $this->list = $l;
        return $l;
    }

    /**
     * @param $url
     * @param bool $ignoreQuery
     * @return array
     */
    public function trimByUrl($url, $ignoreQuery = true) {
        $url = \Tk\Uri::create($url);
        $l = array();
        foreach ($this->list as $t => $u) {
            if ($ignoreQuery) {
                if (\Tk\Uri::create($u)->getRelativePath() == $url->getRelativePath()) {
                    break;
                }
            } else {
                if (\Tk\Uri::create($u)->toString() == $url->toString()) {
                    break;
                }
            }
            $l[$t] = $u;
        }
        $this->list = $l;
        return $l;
    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $template = $this->getTemplate();

        $i = 0;
        foreach ($this->list as $title => $url) {
            $repeat = $template->getRepeat('li');
            if (!$repeat) continue;         // ?? why and how does the repeat end up null.
            if ($i < count($this->list)-1) {
                $repeat->setAttr('url', 'href', \Tk\Uri::create($url)->toString());
                $repeat->insertText('url', $title);
            } else {    // Last item
                $repeat->insertText('li', $title);
                $repeat->addCss('li','active');
            }

            $repeat->appendRepeat();
            $i++;
        }

        return $template;
    }

    /**
     * DomTemplate magic method
     *
     * @return \Dom\Template
     */
    public function __makeTemplate()
    {
        $html = <<<HTML
<ol class="breadcrumb" var="breadcrumb">
  <li repeat="li" var="li"><a href="#" var="url"></a></li>
</ol>
HTML;

        return \Dom\Loader::load($html);
    }


}
