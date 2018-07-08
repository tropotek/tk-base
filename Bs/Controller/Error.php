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

        $template->setTitleText('Error: ' . $this->params['class']);
        $template->insertText('class', $this->params['class']);
        $template->insertHtml('message', $this->params['message'] . ' ' . $this->params['extra']);
        if ($this->params['trace']) {
            $template->insertHtml('trace', $this->params['trace']);
            $template->setChoice('trace');
        }
        if ($this->params['log']) {
            $template->insertHtml('log', $this->params['log']);
            $template->setChoice('log');
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
        return \Dom\Template::loadFile($this->getConfig()->get('template.xtpl.path').'/error.html');
    }

}