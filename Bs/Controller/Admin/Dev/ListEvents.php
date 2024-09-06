<?php
namespace Bs\Controller\Admin\Dev;

use Bs\ControllerAdmin;
use Bs\Db\Permissions;
use Bs\Table;
use Dom\Template;

/**
 *
 */
class ListEvents extends ControllerAdmin
{
    protected Table $table;

    public function doDefault(): void
    {
        $this->getPage()->setTitle('Tail Log');
        $this->setAccess(Permissions::PERM_ADMIN);

        $this->table = new Table('evt');
        $this->table->appendCell('name');
        $this->table->appendCell('value');
        $this->table->appendCell('eventClass');
        $this->table->appendCell('doc')
            ->addHeaderCss('max-width');

        // execute actions and set table orderBy from request
        $this->table->execute();

        $path = $this->getSystem()->makePath($this->getConfig()->get('path.vendor.org'));
        $rows = $this->convertEventData($this->getAvailableEvents($path));
        $this->table->setRows($rows);

    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setAttr('back', 'href', $this->getBackUrl());

        //$this->table->addRowCss('text-nowrap');
        $this->table->getRenderer()->setFooterEnabled(false);
        $this->table->addCss('table-hover');
        $template->appendTemplate('content', $this->table->show());

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
  <div class="page-actions card mb-3">
    <div class="card-header"><i class="fa fa-cogs"></i> Actions</div>
    <div class="card-body" var="actions">
      <a href="/" title="Back" class="btn btn-outline-secondary" var="back"><i class="fa fa-arrow-left"></i> Back</a>
    </div>
  </div>
  <div class="card mb-3">
    <div class="card-header" var="title"><i class="fa fa-calendar"></i> </div>
    <div class="card-body" var="content">
        <p>A list of Events that are available to the EventDispatcher:</p>
    </div>
  </div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

    protected function convertEventData(array $eventData): array
    {
        $data = array();
        foreach ($eventData as $className => $eventArray) {

            foreach ($eventArray['const'] as $consName => $constData) {
                $data[] = array(
                    'name' => '\\'.$className . '::' . $consName,
                    'value' => $constData['value'],
                    'eventClass' => '\\'.$constData['event'],
                    'doc' => nl2br($constData['doc'])
                );
            }
        }
        return $data;
    }

    /**
     * Search the requested path for Event definition files
     * containing the Event constants.
     * This way we can list and document them automatically
     */
    public function getAvailableEvents(string $searchPath, string $fileReg = '/.+Events.php$/'): array
    {
        if (!is_dir($searchPath)) {
            throw new \Tk\Exception('Cannot open file path: ' . $searchPath);
        }
        $directory = new \RecursiveDirectoryIterator($searchPath);
        $flattened = new \RecursiveIteratorIterator($directory);
        $files = new \RegexIterator($flattened, $fileReg);
        $eventData = array();
        foreach ($files as $file) {
            $arr = $this->getClassEvents(file_get_contents($file->getPathname()));
            $eventData = array_merge($eventData, $arr);
        }
        return $eventData;
    }

    /**
     * Parse a php file for all available event codes
     * so we can document them dynamically
     */
    private function getClassEvents(string $phpcode): array
    {
        $classes = array();

        $namespace = 0;
        $tokens = token_get_all($phpcode);
        $count = count($tokens);
        $dlm = false;

        $const = false;
        $name = '';
        $doc = '';
        $event = '';
        $className = '';

        for ($i = 2; $i < $count; $i++) {
            if ((isset($tokens[$i - 2][1]) && ($tokens[$i - 2][1] == "phpnamespace" || $tokens[$i - 2][1] == "namespace")) ||
                ($dlm && $tokens[$i - 1][0] == T_NS_SEPARATOR && $tokens[$i][0] == T_STRING)
            ) {
                if (!$dlm) $namespace = 0;
                if (isset($tokens[$i][1])) {
                    $namespace = $namespace ? $namespace . "\\" . $tokens[$i][1] : $tokens[$i][1];
                    $dlm = true;
                }
            } elseif ($dlm && ($tokens[$i][0] != T_NS_SEPARATOR) && ($tokens[$i][0] != T_STRING)) {
                $dlm = false;
            }
            if (($tokens[$i - 2][0] == T_CLASS || (isset($tokens[$i - 2][1]) && $tokens[$i - 2][1] == "phpclass"))
                && $tokens[$i - 1][0] == T_WHITESPACE && $tokens[$i][0] == T_STRING
            ) {
                $class_name = $tokens[$i][1];
                $className = $namespace . '\\' . $class_name;
                $classes[$className]['class'] = $className;
                $classes[$className]['const'] = array();
            }

            if (is_array($tokens[$i])) {
                if ($tokens[$i][0] != T_WHITESPACE) {
                    if ($tokens[$i][0] == T_CONST && $tokens[$i][1] == 'const') {
                        $const = true;
                        $name = '';
                        $event = '';
                        $doc = '';
                        if (isset($tokens[$i - 2][1])) {
                            $doc = $tokens[$i - 2][1];
                            // Parse out comment (NOTE: The doc is the first part wo we could look for the first @ and call that the end of the doc)
                            $doc = str_replace(array('@var string', '/*', '*/', '*'), '', $doc);
                            preg_match('/(.?)(@event .+)/i', $doc, $reg);
                            if (isset($reg[2])) {
                                $event = trim(trim(str_replace('@event', '', $reg[2])), '\\');
                                $doc = trim(str_replace($reg[2], '', $doc));
                                $doc = preg_replace('/\s+/', ' ', $doc);
                                $doc = preg_replace('/([\.:]) /', "$1\n", $doc);
                                // remove duplicate whitespace
                                $doc = preg_replace("/\s\s([\s]+)?/", " ", $doc);
                            }
                        }
                    } else if ($tokens[$i][0] == T_STRING && $const) {
                        $const = false;
                        $name = $tokens[$i][1];
                    } else if ($tokens[$i][0] == T_CONSTANT_ENCAPSED_STRING && $name && isset($classes[$className])) {
                        $classes[$className]['const'][$name] = array('value' => str_replace(array("'", '"') , '' , $tokens[$i][1]), 'doc' => $doc, 'event' => $event);
                        $doc = '';
                        $name = '';
                        $event = '';
                    }
                }
            } else if ($tokens[$i] != '=') {
                $const = false;
                $doc = '';
                $name = '';
                $event = '';
            }

        }
        return $classes;
    }
}