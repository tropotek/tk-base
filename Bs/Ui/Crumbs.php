<?php
namespace Bs\Ui;

use Dom\Template;
use Tk\Traits\SystemTrait;
use Tk\Uri;

/**
 * Use this object to track and render a crumb stack
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

    /**
     * @var array|Uri[]
     */
    protected array $crumbList = [];

    protected string $homeTitle;

    protected Uri $homeUrl;


    protected function __construct()
    {
        $this->setHomeTitle('Home');
        $this->setHomeUrl(Uri::create('/home'));
    }

    public static function create(): static
    {
        return new static();
    }

    public function __serialize()
    {
        return [
            'visible'   => $this->visible,
            'crumbList' => $this->crumbList,
            'homeTitle' => $this->homeTitle,
            'homeUrl'   => $this->homeUrl,
        ];
    }

    public function __unserialize($data)
    {
        $this->visible   = $data['visible'];
        $this->crumbList = $data['crumbList'];
        $this->homeTitle = $data['homeTitle'];
        $this->homeUrl   = $data['homeUrl'];
    }

    // TODO: This should be in the Factory so instance control is from there....
//    public static function instance(): static
//    {
//        if (!self::$instance) {
//            $crumbs = self::create($homeUrl, $homeTitle);
//            if ($crumbs->getSession()->has($crumbs->getSid())) {
//                $crumbs->setList($crumbs->getSession()->get($crumbs->getSid()));
//            }
//            if (!count($crumbs->getList())) {
//                $crumbs->addCrumb($crumbs->getHomeTitle(), $crumbs->getHomeUrl());
//            }
//            self::$instance = $crumbs;
//        }
//        return self::$instance;
//    }


    public function reset(): static
    {
        if (!$this->getRequest()->query->has(self::CRUMB_IGNORE)) {
            $this->setCrumbList([]);
            $this->addCrumb($this->getHomeTitle(), $this->getHomeUrl());
        }
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

    public function getCrumbList(): array
    {
        return $this->crumbList;
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

    public function getHomeUrl(): Uri
    {
        return $this->homeUrl;
    }

    public function setHomeUrl(Uri $url): static
    {
        $this->homeUrl = $url;
        return $this;
    }

    /**
     * Use to restore crumb list.
     * format:
     *   array(
     *     'Page Name' => Uri::create('/page/url/pageUrl.html')
     *   );
     */
    public function setCrumbList(array $crumbList): static
    {
        $this->crumbList = $crumbList;
        return $this;
    }

    public function getBackUrl(): Uri
    {
        $url = null;
        if (count($this->crumbList) == 1) {
            $url = end($this->crumbList);
        }
        if (count($this->crumbList) > 1) {
            end($this->crumbList);
            $url = prev($this->crumbList);
        }
        return Uri::create($url);
    }

    public function addCrumb(string $title, Uri|string $url): static
    {
        if ($url->getRelativePath() == $this->getHomeUrl()->getRelativePath()) {
            $this->crumbList[$this->getHomeTitle()] = $this->getHomeUrl();
        } else {
            $url = Uri::create($url);
            $this->crumbList[$title] = $url;
        }
        return $this;
    }

    public function replaceCrumb(string $title, Uri|string $url): static
    {
        array_pop($this->crumbList);
        return $this->addCrumb($title, $url);
    }

    public function trimByTitle(string $title): array
    {
        $l = [];
        foreach ($this->crumbList as $t => $u) {
            if ($title == $t) break;
            $l[$t] = $u;
        }
        $this->crumbList = $l;
        return $l;
    }

    public function trimByUrl(Uri|string $url, bool $ignoreQuery = true): array
    {
        $url = Uri::create($url);
        $l = [];
        foreach ($this->crumbList as $t => $u) {
            if ($ignoreQuery) {
                if (Uri::create($u)->getRelativePath() == $url->getRelativePath()) {
                    break;
                }
            } else {
                if (Uri::create($u)->toString() == $url->toString()) {
                    break;
                }
            }
            $l[$t] = $u;
        }
        $this->crumbList = $l;
        return $l;
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();

        $i = 0;
        foreach ($this->crumbList as $title => $url) {
            $repeat = $template->getRepeat('item');
//            if (!$repeat) continue;         // ?? why and how does the repeat end up null.
            if ($i < count($this->crumbList) - 1) {
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
