<?php

namespace Bs\Ui;

use Tk\Uri;


/**
 * Manage a sites breadcrumb trail
 *
 * Call the `Breadcrumbs::init()` method from your boostrap stage after the session has been started.
 * Use `Breadcrumbs::instance()` if you require access to instance methods that are not static.
 *
 * You can change the home URL and title/icon using `Breadcrumbs::setHome($url, $title)`.
 * Suggest changing the url and title for public vs registered users. Change this after you call init().
 *
 * Calling `Breadcrumbs::reset()` will set the stack back to the home URL and current URL (if not the home URL).
 *
 * Call `Breadcrumbs::setTitle($url, $title)` anytime a page's title gets set.
 *
 * Create your own renderer to display the crumb trail based on your templates requirements.
 * Get the list of urls and titles by calling `Breadcrumbs::toArray()`
 *
 */
class Breadcrumbs
{
    protected static mixed $_instance = null;

    const string CRUMB_IGNORE = 'crumb_ignore';
    const string CRUMB_RESET  = 'crumb_reset';
    const string SID          = '__breadcrumbs';

    protected bool   $visible       = true;
    protected array  $crumbStack    = [];
    protected array  $titleStack    = [];
    protected string $homeTitle     = 'Home';
    protected string $homeUrl       = '/';
    protected int    $maxLength     = 8;


    protected function __construct()
    {
        $this->homeUrl   = Uri::create('/')->toRelativeString();
        $this->homeTitle = 'Home';
    }

    public function __serialize()
    {
        return [
            'visible'    => $this->visible,
            'crumbStack' => $this->crumbStack,
            'titleStack' => $this->titleStack,
            'homeTitle'  => $this->homeTitle,
            'homeUrl'    => $this->homeUrl,
            'maxLength'  => $this->maxLength,
        ];
    }

    public function __unserialize(array $data)
    {
        $this->visible    = $data['visible'];
        $this->crumbStack = $data['crumbStack'];
        $this->titleStack = $data['titleStack'];
        $this->homeTitle  = $data['homeTitle'];
        $this->homeUrl    = $data['homeUrl'];
        $this->maxLength  = $data['maxLength'];
    }

    public static function instance(): self
    {
        if (is_null(self::$_instance)) {
            self::$_instance = $_SESSION[self::SID] ?? new self();
            $_SESSION[self::SID] = self::$_instance;
        }
        return self::$_instance;
    }

    public static function init(): static
    {
        if (!is_null(self::$_instance)) return self::$_instance;
        $crumbs = self::instance();

        if (isset($_GET[self::CRUMB_IGNORE])) return $crumbs;

        if (!self::count()) {
            $crumbs->crumbStack[] = $crumbs->homeUrl;
            $crumbs->titleStack[] = $crumbs->homeTitle;
        }

        // add new crumb when retrieving initial instance
        self::pushCrumb(Uri::create());

        return $crumbs;
    }

    /**
     * delete crumbs from session
     * self::instance() needs to be called to re-create a new crumb stack
     */
    public static function destroy(): void
    {
        self::$_instance = null;
        unset($_SESSION[self::SID]);
    }

    public static function reset(): self
    {
        $crumbs = self::instance();

        $crumbs->crumbStack = [];
        $crumbs->titleStack = [];

        if ($crumbs->homeUrl) {
            $crumbs->crumbStack[] = $crumbs->homeUrl;
            $crumbs->titleStack[] = $crumbs->homeTitle;
        }

        Breadcrumbs::pushCrumb(Uri::create());

        return $crumbs;
    }

    public static function setHome(string|Uri $homeUrl, string $homeTitle): static
    {
        $crumbs = self::instance();

        $i = self::getIndex($crumbs->homeUrl);

        $homeUrl = Uri::create($homeUrl);
        $crumbs->homeUrl = $homeUrl->toRelativeString();
        $crumbs->homeTitle = $homeTitle;

        if ($i !== false) {
            $crumbs->crumbStack[$i] = $crumbs->homeUrl;
            $crumbs->titleStack[$i] = $crumbs->homeTitle;
        }

        return $crumbs;
    }

    public static function pushCrumb(Uri|string $url, ?string $title = null): static
    {
        $crumbs = self::instance();

        if (isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) != 'GET') return $crumbs;

        $url = Uri::create($url);
        $rel = $url->toRelativeString();

        if ($rel == $crumbs->current()->toRelativeString()) return $crumbs;
        if ($crumbs->isHomeUrl($rel)) return $crumbs;

        if (is_null($title)) $title = basename($rel);

        // trim stack to url if already set
        $i = self::getIndex($url);
        if ($i !== false) {
            $crumbs->crumbStack = array_slice($crumbs->crumbStack, 0, $i);
            $crumbs->titleStack = array_slice($crumbs->titleStack, 0, $i);
        }

        $crumbs->crumbStack[] = $rel;
        $crumbs->titleStack[] = trim($title);

        // trim stack length
        $crumbs->trim();

        return $crumbs;
    }

    /**
     * Set/update the title of an existing crumb if it exists
     * The position of the crumb is not changed
     */
    public static function setTitle(Uri|string $url, string $title): static
    {
        $crumbs = self::instance();

        if ($crumbs->isHomeUrl($url->toRelativeString())) return $crumbs;
        $i = self::getIndex($url);
        if ($i !== false) {
            $crumbs->titleStack[$i] = trim($title);
        }

        return $crumbs;
    }

    public static function getIndex(Uri|String $url): int|false
    {
        $crumbs = self::instance();

        $url = Uri::create($url);
        $i = array_search($url->toRelativeString(), $crumbs->crumbStack);
        if (!is_numeric($i)) {
            return false;
        }
        return intval($i);
    }

    public static function current(): Uri
    {
        $crumbs = self::instance();

        $i = self::count() - 1;
        if ($i < 0) return Uri::create($crumbs->homeUrl);
        return Uri::create($crumbs->crumbStack[$i]);
    }

    public static function previous(): Uri
    {
        $crumbs = self::instance();
        $curr = Uri::create()->toRelativeString();
        $stack = $crumbs->getCrumbStack();
        do {
            $url = array_pop($stack);
        } while (count($stack) && $curr == $url);
        return Uri::create($url);
    }

    public static function count(): int
    {
        $crumbs = self::instance();
        return count($crumbs->crumbStack);
    }

    public static function toArray(): array
    {
        $crumbs = self::instance();
        return array_combine($crumbs->crumbStack, $crumbs->titleStack);
    }

    /**
     * trim the stack to the max allowable length
     * the oldest crumbs will be removed first, the home URL will always be the first crumb
     */
    protected function trim(): static
    {
        if ($this->count() <= $this->maxLength) return $this;

        // trim the stack
        $max = $this->maxLength - 1;
        $this->crumbStack = array_slice($this->crumbStack, -$max);
        $this->titleStack = array_slice($this->titleStack, -$max);

        // prepend the home url
        array_unshift($this->crumbStack, $this->homeUrl);
        array_unshift($this->titleStack, $this->homeTitle);

        return $this;
    }


    public function getCrumbStack(): array
    {
        return $this->crumbStack;
    }

    public function getTitleStack(): array
    {
        return $this->titleStack;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function setVisible(bool $v): static
    {
        $this->visible = $v;
        return $this;
    }

    public function isHomeUrl(string|Uri $url): bool
    {
        $url = Uri::create($url);
        if (in_array($url->toRelativeString(), [$this->homeUrl, '/'])) return true;
        return false;
    }

}