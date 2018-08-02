<?php
namespace Bs\Controller;

use Tk\Request;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Error extends Iface
{
    /**
     * @var null|\Exception
     */
    protected $e = null;

    /**
     * @var bool
     */
    protected $withTrace = false;

    /**
     * @var array
     */
    protected $params = array();



    /**
     * @param Request $request
     */
    public function doDefault(Request $request, \Exception $e, $withTrace = false)
    {
        $this->e = $e;
        $this->withTrace = $withTrace;

        $this->params = array(
            'class' => get_class($this->e),
            'message' => $this->e->getMessage(),
            'trace' => '',
            'extra' => '',
            'log' => ''
        );
        if ($this->withTrace) {
            $toString = trim($this->e->__toString());
            if (is_readable($this->getConfig()->get('log.session'))) {
                $sessionLog = file_get_contents($this->getConfig()->get('log.session'));
                if (class_exists('SensioLabs\AnsiConverter\AnsiToHtmlConverter')) {
                    $converter = new \SensioLabs\AnsiConverter\AnsiToHtmlConverter();
                    $sessionLog = $converter->convert($sessionLog);
                }
                $this->params['log'] = sprintf('<div class="content"><p><b>System Log:</b></p>'.
                    '<pre class="console" style="color: #666666; background-color: #000; padding: 10px 15px; font-family: monospace;">%s</pre> <p>&#160;</p></div>',
                    $sessionLog);
            }
            $this->params['trace'] = str_replace(array("&lt;?php&nbsp;<br />", 'color: #FF8000'), array('', 'color: #666'),
                highlight_string("<?php \n" . $toString, true));
            $this->params['extra'] = sprintf('<br/>in <em>%s:%s</em>',  $this->e->getFile(), $this->e->getLine());
        }

    }

    /**
     * @return \Dom\Template
     * @throws \Dom\Exception
     */
    public function show()
    {
        $template = parent::show();

        $template->setAttr('base-url', 'href', $this->getConfig()->getSiteUrl() . dirname($this->getConfig()->get('template.error')) . '/');

        $url = $this->getConfig()->getSiteUrl() . '/';
        if ($this->getUser()) {
            $url = $this->getConfig()->getUserHomeUrl()->getPath();
        }
        if ($this->getConfig()->getBackUrl()) {
            $url = $this->getConfig()->getBackUrl();
        }
        $template->setAttr('home-url', 'href', $url);

        if (!$this->getConfig()->isDebug()) {
            $template->setTitleText('Error: ' . $this->params['class']);
            $template->insertText('class', $this->params['class']);
            $template->appendHtml('message', $this->params['message'] . ' ' . $this->params['extra']);
            if ($this->params['trace']) {
                $template->appendHtml('trace', $this->params['trace']);
                $template->setChoice('trace');
            }
            if ($this->params['log']) {
                $template->appendHtml('log', $this->params['log']);
                $template->setChoice('log');
            }
        } else if ($this->e->getCode() == \Tk\Response::HTTP_NOT_FOUND) {
            $template->insertText('class', '404 Error Page Not Found');
            $template->appendHtml('message', 'Page not found. If you find this page, please let us know.');
        } else {
            $template->insertText('class', '500 Error Internal Server Error');
            $template->appendHtml('message', 'Something went very wrong. We are sorry for that. ');
        }

        return $template;
    }


    /**
     * DomTemplate magic method
     *
     * @return \Dom\Template
     * @throws \Dom\Exception
     */
    public function __makeTemplate()
    {
        return \Dom\Template::loadFile($this->getConfig()->getSitePath() . $this->getConfig()->get('template.error'));
    }

}