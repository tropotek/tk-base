<?php
namespace Bs\Menu;

use Tk\Exception;
use Tk\Uri;

class Item
{
    CONST string TYPE_ROOT      = 'root';           // root menu
    CONST string TYPE_SUB_MENU  = 'submenu';        // has children, no url
    CONST string TYPE_LINK      = 'link';           // no children
    CONST string TYPE_HEADER    = 'header';         // no children, no url
    CONST string TYPE_SEPARATOR = 'separator';      // no children, no badge, no url, no icon

    CONST array TYPES_LIST = [
        self::TYPE_ROOT,
        self::TYPE_HEADER,
        self::TYPE_SUB_MENU,
        self::TYPE_LINK,
        self::TYPE_SEPARATOR
    ];

    public string $name      = '';
    public string $icon      = '';
    public string $type      = self::TYPE_ROOT;
    public ?Uri   $url       = null;
    public mixed  $visible   = true;        // bool|callable
    public array  $context   = [];          // any params for the renderer
    public ?Item  $parent    = null;
    public ?Item  $root      = null;
    public int    $level     = 0;
    private int   $maxLevels = 3;           // should use getMaxLevels() to get root value

    /** @var array<string, Item> */
    public array  $children  = [];


    public function __construct(string $name = 'menu', int $maxLevels = 3)
    {
        $this->name = $name;
        $this->type = self::TYPE_ROOT;
        $this->root = $this;
        $this->maxLevels = $maxLevels;
    }

    public function add(
        string $type = self::TYPE_LINK,
        string $name = '',
        ?Uri $url = null,
        string $icon = '',
        null|callable|bool $visible = null,
        array $context = [],
    ): Item
    {
        if ($this->level+1 > $this->getMaxLevels()) {
            throw new Exception("max levels reached for sub-menu {$this->name}");
        }

        $item = new self();
        $item->type = $type;
        $item->name = $name;
        $item->url = $url;
        $item->icon = $icon;
        $item->visible = $visible;
        $item->context = $context;

        $item->parent = $this;
        $item->level = $this->level + 1;
        $item->root = $this->root;
        $this->children[$item->name] = $item;

        return $item;
    }

    public function addItem(Item $src): Item
    {
        return $this->add(
            $src->type,
            $src->name,
            $src->url,
            $src->icon,
            $src->visible,
            $src->context
        );
    }

    public function addSubmenu(string $name, string $icon, null|callable|bool $visible = null, array $context = []): Item
    {
        return $this->add(
            self::TYPE_SUB_MENU,
            $name,
            null,
            $icon,
            $visible,
            $context
        );
    }

    public function addLink(string $name, ?Uri $url, string $icon, null|callable|bool $visible = null, array $context = []): Item
    {
        return $this->add(
            self::TYPE_LINK,
            $name,
            $url,
            $icon,
            $visible,
            $context
        );
    }

    public function addHeader(string $name, string $icon = '', null|callable|bool $visible = null, array $context = []): Item
    {
        return $this->add(
            self::TYPE_HEADER,
            $name,
            null,
            $icon,
            $visible,
            $context
        );
    }

    public function addSeparator(null|callable|bool $visible = null, array $context = []): Item
    {
        static $idx = 0;
        $idx++;
        return $this->add(
            self::TYPE_SEPARATOR,
            "sep-{$idx}",
            null,
            '',
            $visible,
            $context
        );
    }

    public function getMaxLevels(): int
    {
        return $this->root->maxLevels;
    }

    public function isVisible(): bool
    {
        if (is_bool($this->visible)) return $this->visible;
        if (is_callable($this->visible)) return (bool) call_user_func($this->visible, [$this]);
        return true;
    }

    public function hasChildren(): bool
    {
        return count($this->children) > 0;
    }

    public function getId(): string
    {
        return 'item-'. preg_replace('/[^0-9a-z]/', '-', strtolower($this->name));
    }



    /**
     * Convert all `header` items to `submenu` items with children
     * returns a new instance of the menu
     * can only be executed from the root menu item
     */
    public function convertHeaderToSubmenu(): self
    {
        if ($this !== $this->root) {
            throw new Exception("can only be executed from the root menu item");
        }
        return $this->convertSubmenu($this, new self());
    }

    private function convertSubmenu(Item $src, Item $dst): Item
    {
        $header = null;
        foreach ($src->children as $child) {
            if ($child->type === self::TYPE_HEADER) {
                $header = $dst->addSubmenu($child->name, $child->icon, $child->visible, $child->context);
                $header->icon = $child->icon;
                continue;
            }

            if ($header) {
                $sub = $header->addItem($child);
            } else {
                $sub = $dst->addItem($child);
            }

            if ($child->type === self::TYPE_SUB_MENU) {
                $this->convertSubmenu($child, $sub);
            }
        }

        return $dst;
    }

    /**
     * return a string representation of this menu
     */
    public function __toString(): string
    {
        return sprintf("- {%s} root %s\n", $this->level, $this->name) . $this->iterateString($this);
    }

    protected function iterateString(Item $item): string
    {
        $str = '';
        foreach ($item->children as $child) {
            $s = sprintf('%s- {%s} ',
                str_pad('', $child->level*2, ' '),
                $child->level
            );
            switch ($child->type) {
                case self::TYPE_HEADER:
                    $s .= sprintf('__%s__', $child->name);
                    break;
                case self::TYPE_SEPARATOR:
                    $s .= sprintf('-------- [%s]', $child->name);
                    break;
                case self::TYPE_LINK:
                    $s .= sprintf('%s [%s]', $child->name, is_null($child->url) ? '' : $child->url->toRelativeString());
                    break;
                case self::TYPE_SUB_MENU:
                    $s .= sprintf('* %s', $child->name) . "\n";
                    $s .= $this->iterateString($child);
                    break;
            }
            $str .= $s . "\n";
        }
        return $str;
    }

}
