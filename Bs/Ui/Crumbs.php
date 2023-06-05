<?php
namespace Bs\Ui;

use Dom\Template;
use Tk\Traits\SystemTrait;

/**
 * Use this object to track and render a crumb stack.
 * All url's used should be relative to the site.
 */
class Crumbs extends \Dom\Renderer\Renderer implements \Dom\Renderer\DisplayInterface
{
    use SystemTrait;

    /**
     * Request param: Reset the crumb stack
     */
    const CRUMB_RESET = 'crumb_reset';

    /**
     * Request param: Do not add the current URI to the crumb stack
     */
    const CRUMB_IGNORE = 'crumb_ignore';


    protected bool $visible = true;

    protected array $crumbStack = [];

    protected string $homeTitle = '';

    protected string $homeUrl = '';

    protected int $trim = 0;


    protected function __construct()
    {
        $this->setHomeTitle('Home');
        $this->setHomeUrl('/home');
    }

    public static function create(): static
    {
        return new static();
    }

    public function __serialize()
    {
        return [
            'visible'   => $this->visible,
            'crumbList' => $this->crumbStack,
            'homeTitle' => $this->homeTitle,
            'homeUrl'   => $this->homeUrl,
            'trim'      => $this->trim,
        ];
    }

    public function __unserialize($data)
    {
        $this->visible    = $data['visible'];
        $this->crumbStack = $data['crumbList'];
        $this->homeTitle  = $data['homeTitle'];
        $this->homeUrl    = $data['homeUrl'];
        $this->trim       = $data['trim'];
    }

    public function getCrumbStack(): array
    {
        return $this->crumbStack;
    }

    public function getHomeTitle(): string
    {
        return $this->homeTitle;
    }

    public function setHomeTitle(string $homeTitle): static
    {
        $this->homeTitle = $homeTitle;
        return $this;
    }

    public function getHomeUrl(): string
    {
        return $this->homeUrl;
    }

    public function setHomeUrl(string $url): static
    {
        $url = \Tk\Uri::create($url)->getRelativePath();
        $this->homeUrl = $url;
        return $this;
    }

    public function getTrim(): int
    {
        return $this->trim;
    }

    /**
     * Set a maximum number of crumbs the stack can have.
     * When Crumbs::trim() is called then the first crumbs are removed
     *  from the stack excluding the home page.
     */
    public function setTrim(int $trim): Crumbs
    {
        $this->trim = $trim;
        return $this;
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

    public function getBackUrl(): string
    {
        if (!count($this->getCrumbStack())) return '';

        $cUrl = '';
        $copy = array_keys($this->crumbStack);
        do {
            $bUrl = array_pop($copy);
        } while (count($copy) && $cUrl == $bUrl);
        return $bUrl;
    }

    /**
     * Reset the crumb stack with the homepage as the first crumb
     */
    public function reset(): static
    {
        if ($this->getRequest()->query->has(self::CRUMB_IGNORE))  return $this;
        $this->crumbStack = [];
        $this->crumbStack[$this->getHomeUrl()] = $this->getHomeTitle();
        return $this;
    }

    public function addCrumb(string $url, string $title = ''): static
    {
        $url = \Tk\Uri::create($url)->getRelativePath();
        //vd($url, $this->getHomeUrl());
        if ($url == $this->getHomeUrl()) return $this;
        if (!$title) $title = basename($url);
        $this->crumbStack[$url] = $title;
        return $this;
    }

    /**
     * @deprecated I do not think this is needed anymore
     */
    public function trimByTitle(string $title): array
    {
        $l = [];
        foreach ($this->crumbStack as $u => $t) {
            $l[$u] = $t;
            if ($title == $t) break;
        }
        $this->crumbStack = $l;
        return $l;
    }

    public function trimByUrl(string $url): array
    {
        $l = [];
        foreach ($this->crumbStack as $u => $t) {
            $l[$u] = $t;
            if ($u == $url) break;
        }
        $this->crumbStack = $l;
        return $l;
    }

    public function trim(): array
    {
        $l = [];
        $i = 0;
        $start = count($this->getCrumbStack()) - $this->getTrim()+1;
        if ($start < 1) return $this->getCrumbStack();
        foreach ($this->crumbStack as $u => $t) {
            if (!$i) $l[$u] = $t;
            if ($i >= $start) {
                $l[$u] = $t;
            }
            $i++;
        }
        $this->crumbStack = $l;
        return $l;
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();

        $i = 0;
        $last = count($this->crumbStack) - 1;
        foreach ($this->getCrumbStack() as $url => $title) {
            $repeat = $template->getRepeat('item');
            if ($i < $last) {
                $repeat->setAttr('url', 'href', $url);
                $repeat->insertHtml('url', $title);
            } else {    // Last item
                $repeat->insertHtml('item', $title);
                $repeat->addCss('item', 'active');
                $repeat->setAttr('item', 'aria-current', 'page');
            }

            $repeat->appendRepeat();
            $i++;
        }

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb p-3 bg-body-tertiary rounded-3">
      <li class="breadcrumb-item" repeat="item"><a href="#" var="url"></a></li>
    </ol>
  </nav>
</div>
HTML;

        return $this->loadTemplate($html);
    }


}
