<?php

namespace Bs\Menu;

use Dom\Repeat;
use Dom\Template;

/**
 * Render Menu Items for the Minton template
 */
class MintonRenderer
{

    protected Item $menu;

    public function __construct(Item $menu)
    {
        $this->menu = $menu;
    }

    public function showTopNav(): Template
    {
        $html = <<<HTML
<div class="collapse navbar-collapse" id="topnav-menu-content">
    <ul class="navbar-nav" var="ul">
        <li class="nav-item" repeat="li">
            <div repeat="span"></div>
            <a class="nav-link arrow-none" href="#" id="" role="button" repeat="link">
                <i var="icon"></i> <span var="name"></span> <span class="arrow-down" choice="is-dropdown"></span>
            </a>
        </li>
    </ul>

    <div class="dropdown" repeat="dropdown">
        <a class="dropdown-toggle arrow-none" href="#" id="" role="button"
            data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" var="nav-link">
            <i var="nav-icon"></i> <span var="nav-name"></span> <span class="arrow-down"></span>
        </a>
        <div class="dropdown-menu" aria-labelledby="" var="dropdown-menu">
            <div repeat="span"></div>
            <a class="dropdown-item" href="#" id="" role="button" repeat="link">
                <i var="icon"></i> <span var="name"></span>
            </a>
        </div>
    </div>
</div>
HTML;
        $template = Template::load($html);

        $this->menu = $this->menu->convertHeaderToSubmenu();

        foreach ($this->menu->children as $child) {
            $li = $template->getRepeat('li');

            if ($child->type == Item::TYPE_HEADER) continue;
            switch ($child->type) {
                case Item::TYPE_SEPARATOR:
                    $span = $li->getRepeat('span');
                    $span->addCss('span', 'dropdown-divider');
                    //$span->setHtml('span', '<hr>');
                    $span->setVisible('span');
                    $span->appendRepeat();
                    break;
                case Item::TYPE_LINK:
                    $link = $li->getRepeat('link');
                    $link->setText('name', $child->name);
                    $link->addCss('icon', $child->icon);
                    $link->setAttr('link', 'href', $child->url);
                    $link->setVisible('link');
                    $link->appendRepeat();
                    break;
                case Item::TYPE_SUB_MENU:
                    $dd = $this->iterateTopDropdown($template , $child);
                    $li->appendTemplate('li', $dd);
                    break;
            }
            $li->appendRepeat();
        }

        return $template;
    }

    protected function iterateTopDropdown(Template $template, Item $item): Repeat
    {
        $li = $template->getRepeat('dropdown');

        $li->addCss('nav-icon', $item->icon . ' me-1');
        $li->setText('nav-name', $item->name);
        $li->setAttr('nav-link', 'id', 'nl-'.$item->getId());
        $li->setAttr('dropdown-menu', 'aria-labelledby', 'nl-'.$item->getId());
        if ($item->level == 1) {
            $li->addCss('nav-link', 'nav-link');
        } else {
            $li->addCss('nav-link', 'dropdown-item');
        }

        foreach ($item->children as $child) {
            if ($child->type == Item::TYPE_HEADER) continue;
            switch ($child->type) {
                case Item::TYPE_SEPARATOR:
                    $span = $li->getRepeat('span');
                    $span->addCss('span', 'dropdown-divider');
                    //$span->setHtml('span', '<hr>');
                    $span->setVisible('span');
                    $span->appendRepeat('dropdown-menu');
                    break;
                case Item::TYPE_LINK:
                    $link = $li->getRepeat('link');
                    $link->setText('name', $child->name);
                    $link->addCss('icon', $child->icon);
                    $link->setAttr('link', 'href', $child->url);
                    $link->setVisible('link');
                    $link->appendRepeat('dropdown-menu');
                    break;
                case Item::TYPE_SUB_MENU:
                    $sub = $this->iterateTopDropdown($template, $child);
                    $li->appendTemplate('dropdown-menu', $sub);
                    break;
            }
        }
        return $li;
    }


    public function showSideNav(): Template
    {
        $html = <<<HTML
<div id="sidebar-menu" var="menu">
    <ul repeat="ul">
        <li repeat="li">
            <span choice="span"></span>
            <a href="#" choice="link">
                <i class="" var="icon"></i> <span var="name"></span> <span class="menu-arrow" choice="dropdown"></span>
            </a>
            <div class="collapse" id="" var="dropdown-menu" choice="dropdown">
            </div>
        </li>
    </ul>
</div>
HTML;
        $template = Template::load($html);

        $ul = $this->iterateSideNav($template, $this->menu);
        $ul->setAttr('ul', 'id', 'side-menu');
        $ul->appendRepeat();

        return $template;
    }

    protected function iterateSideNav(Template $template, Item $item): Repeat
    {
        $ul = $template->getRepeat('ul');
        if ($item->level > 0) {
            $ul->addCss('ul', 'nav-second-level');
        }
        foreach ($item->children as $child) {
            $li = $ul->getRepeat('li');
            switch ($child->type) {
                case Item::TYPE_HEADER:
                    $li->addCss('li', 'menu-title mt-2');
                    $li->setText('span', $child->name);
                    $li->setVisible('span');
                    break;
                case Item::TYPE_SEPARATOR:
                    $li->addCss('span', 'menu-separator');
                    $li->setHtml('span', '<hr class="m-2">');
                    $li->setVisible('span');
                    break;
                case Item::TYPE_LINK:
                    $li->setText('name', $child->name);
                    $li->addCss('icon', $child->icon);
                    $li->setAttr('link', 'href', $child->url);
                    $li->setVisible('link');
                    break;
                case Item::TYPE_SUB_MENU:
                    $li->addCss('link', 'waves-effect');
                    $li->setText('name', $child->name);
                    $li->addCss('icon', $child->icon);
                    $li->setAttr('link', 'href', '#'.$child->getId());
                    $li->setAttr('dropdown-menu', 'id', $child->getId());
                    $li->setVisible('link');
                    $li->setVisible('dropdown');
                    $li->setAttr('link', [
                        'data-bs-toggle' => 'collapse',
                        'aria-expanded' => 'false',
                    ]);

                    $sub = $this->iterateSideNav($template, $child);
                    $li->appendTemplate('dropdown-menu', $sub);
                    break;
            }
            $li->appendRepeat();
        }
        return $ul;
    }

    public function showProfileNav(): Template
    {
        $html = <<<HTML
<div class="dropdown-menu dropdown-menu-end profile-dropdown" var="menu">
    <div class="dropdown-divider" repeat="separator"></div>
    <a href="#" class="dropdown-item notify-item" repeat="link">
        <i class="" var="icon"></i> <span var="name"></span>
    </a>
</div>
HTML;
        $template = Template::load($html);

        foreach ($this->menu->children as $item) {
            $row = null;
            if ($item->type == 'separator') {
                $row = $template->getRepeat('separator');
            } else if ($item->type == 'link') {
                $row = $template->getRepeat('link');
                $row->setText('name', $item->name);
                $row->setAttr('link', 'href', $item->url);
                $row->addCss('icon', $item->icon . ' me-1');
                if (isset($item->context['attrs'])) {
                    $row->setAttr('link', $item->context['attrs']);
                }
                if (isset($item->context['css'])) {
                    $row->addCss('link', $item->context['css']);
                }
            }

            if ($row) {
                $row->appendRepeat('menu');
            }
        }

        return $template;
    }

}