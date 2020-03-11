<?php
namespace Bs\Listener;

use Bs\Db\User;
use Tk\ConfigTrait;
use Tk\Event\Subscriber;



/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class PageLoaderHandler implements Subscriber
{
    use ConfigTrait;

    const NO_LOADER = 'no-loader';


    /**
     * @param \Tk\Event\Event $event
     * @throws \Exception
     */
    public function showPage($event)
    {

        $controller = \Tk\Event\Event::findControllerObject($event);
        if ($controller instanceof \Bs\Controller\Iface) {
            $page = $controller->getPage();
            if (!$page || !$page->getTemplatePath()) return;
            $template = $page->getTemplate();

            // Do not use for public pages
            $uri = \Bs\Uri::create();
            if ($uri->getRoleType($this->getConfig()::getUserTypeList(true)) == '') {
                return;
            }


            $js = <<<JS
$(document).ready(function() {
  $('body').addClass('loaded');
  $(window).on('beforeunload', function(e) {
    // Do not fire loader for some links... Add class="no-loader" for links that you want to force to not use the loader
    if (e.target && e.target.activeElement &&
        ((e.target.activeElement.href && (
          e.target.activeElement.href.indexOf(config.dataUrl) >= 0 ||
          e.target.activeElement.href.indexOf('mailto:') >= 0)) ||
          $(e.target.activeElement).hasClass('no-loader'))
        ) {
      $('body').addClass('loaded');
      return;
    }
    $('body').removeClass('loaded');
  });
  // .on('blur', function () {
  //   $('body').removeClass('loaded');
  // });

  $(document).keyup(function(e) {
    if (e.key === 'Escape') { // escape key maps to keycode `27`
      $('body').addClass('loaded');
    }
  });

});
JS;
            $template->appendJs($js);



            $template->prependHtml($template->getBodyElement(), '<div id="loader-wrapper">
    <div id="loader"></div>
    <div class="loader-section section-left"></div>
    <div class="loader-section section-right"></div>
 </div>');
            $css = <<<CSS
#loader-wrapper {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 9999;
  opacity: 0.7;
}

#loader {
  display: block;
  position: relative;
  left: 50%;
  top: 50%;
  width: 150px;
  height: 150px;
  margin: -75px 0 0 -75px;
  border-radius: 50%;
  border: 3px solid transparent;
  border-top-color: #3498db;
  -webkit-animation: spin 2s linear infinite; /* Chrome, Opera 15+, Safari 5+ */
  animation: spin 2s linear infinite; /* Chrome, Firefox 16+, IE 10+, Opera */
  z-index: 1001;
}

#loader:before {
  content: "";
  position: absolute;
  top: 5px;
  left: 5px;
  right: 5px;
  bottom: 5px;
  border-radius: 50%;
  border: 3px solid transparent;
  border-top-color: #e74c3c;
  -webkit-animation: spin 3s linear infinite; /* Chrome, Opera 15+, Safari 5+ */
  animation: spin 3s linear infinite; /* Chrome, Firefox 16+, IE 10+, Opera */
}

#loader:after {
  content: "";
  position: absolute;
  top: 15px;
  left: 15px;
  right: 15px;
  bottom: 15px;
  border-radius: 50%;
  border: 3px solid transparent;
  border-top-color: #f9c922;

  -webkit-animation: spin 1.5s linear infinite; /* Chrome, Opera 15+, Safari 5+ */
  animation: spin 1.5s linear infinite; /* Chrome, Firefox 16+, IE 10+, Opera */
}

@-webkit-keyframes spin {
  0% {
    -webkit-transform: rotate(0deg); /* Chrome, Opera 15+, Safari 3.1+ */
    -ms-transform: rotate(0deg); /* IE 9 */
    transform: rotate(0deg); /* Firefox 16+, IE 10+, Opera */
  }
  100% {
    -webkit-transform: rotate(360deg); /* Chrome, Opera 15+, Safari 3.1+ */
    -ms-transform: rotate(360deg); /* IE 9 */
    transform: rotate(360deg); /* Firefox 16+, IE 10+, Opera */
  }
}

@keyframes spin {
  0% {
    -webkit-transform: rotate(0deg); /* Chrome, Opera 15+, Safari 3.1+ */
    -ms-transform: rotate(0deg); /* IE 9 */
    transform: rotate(0deg); /* Firefox 16+, IE 10+, Opera */
  }
  100% {
    -webkit-transform: rotate(360deg); /* Chrome, Opera 15+, Safari 3.1+ */
    -ms-transform: rotate(360deg); /* IE 9 */
    transform: rotate(360deg); /* Firefox 16+, IE 10+, Opera */
  }
}

#loader-wrapper .loader-section {
  position: fixed;
  top: 0;
  width: 51%;
  height: 100%;
  background: #222222;
  z-index: 1000;
  -webkit-transform: translateX(0); /* Chrome, Opera 15+, Safari 3.1+ */
  -ms-transform: translateX(0); /* IE 9 */
  transform: translateX(0); /* Firefox 16+, IE 10+, Opera */
}

#loader-wrapper .loader-section.section-left {
  left: 0;
}

#loader-wrapper .loader-section.section-right {
  right: 0;
}

/* Loaded */
.loaded #loader-wrapper .loader-section.section-left {
  -webkit-transform: translateX(-100%); /* Chrome, Opera 15+, Safari 3.1+ */
  -ms-transform: translateX(-100%); /* IE 9 */
  transform: translateX(-100%); /* Firefox 16+, IE 10+, Opera */

  -webkit-transition: all 0.7s 0.3s cubic-bezier(0.645, 0.045, 0.355, 1.000);
  transition: all 0.7s 0.3s cubic-bezier(0.645, 0.045, 0.355, 1.000);
}

.loaded #loader-wrapper .loader-section.section-right {
  -webkit-transform: translateX(100%); /* Chrome, Opera 15+, Safari 3.1+ */
  -ms-transform: translateX(100%); /* IE 9 */
  transform: translateX(100%); /* Firefox 16+, IE 10+, Opera */

  -webkit-transition: all 0.7s 0.3s cubic-bezier(0.645, 0.045, 0.355, 1.000);
  transition: all 0.7s 0.3s cubic-bezier(0.645, 0.045, 0.355, 1.000);
}

.loaded #loader {
  opacity: 0;
  -webkit-transition: all 0.3s ease-out;
  transition: all 0.3s ease-out;
}

.loaded #loader-wrapper {
  visibility: hidden;

  -webkit-transform: translateY(-100%); /* Chrome, Opera 15+, Safari 3.1+ */
  -ms-transform: translateY(-100%); /* IE 9 */
  transform: translateY(-100%); /* Firefox 16+, IE 10+, Opera */

  -webkit-transition: all 0.3s 1s ease-out;
  transition: all 0.3s 1s ease-out;
}

/* JavaScript Turned Off */
.no-js #loader-wrapper {
  display: none;
}

.no-js h1 {
  color: #222222;
}
CSS;
            $template->appendCss($css);



        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            \Tk\PageEvents::CONTROLLER_SHOW => 'showPage'
        );
    }

}
