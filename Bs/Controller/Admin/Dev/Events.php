<?php
namespace Bs\Controller\Admin\Dev;

use Tk\Request;

/**
 *
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Events extends \Bs\Controller\AdminIface
{

    /**
     * @var \Tk\Table
     */
    protected $table = null;

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        $this->setPageTitle('Available Events');
        $this->getCrumbs()->reset();
    }


    /**
     *
     * @param Request $request
     * @throws \Tk\Exception
     */
    public function doDefault(Request $request)
    {

        $this->table = new \Tk\Table('EventList');
        $this->table->setRenderer(\Tk\Table\Renderer\Dom\Table::create($this->table));

        $this->table->appendCell(new \Tk\Table\Cell\Text('name'));
        $this->table->appendCell(new \Tk\Table\Cell\Text('value'));
        $this->table->appendCell(new \Tk\Table\Cell\Text('eventClass'));
        $this->table->appendCell(new \Tk\Table\Cell\Html('doc'))->addCss('key');

        $this->table->appendAction(\Tk\Table\Action\Csv::create());

        /** @var \Tk\EventDispatcher\EventDispatcher $dispatcher */
        $dispatcher = $this->getConfig()->getEventDispatcher();
        $list = $this->convertEventData($dispatcher->getAvailableEvents(\App\Config::getInstance()->getSitePath()));
        $this->table->setList($list);

    }

    /**
     * @param $eventData
     * @return array
     */
    protected function convertEventData($eventData) {
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
     * @return \Dom\Template
     */
    public function show()
    {
        $template = parent::show();

        $template->appendTemplate('table', $this->table->getRenderer()->show());

        return $template;
    }

    /**
     * DomTemplate magic method
     *
     * @return \Dom\Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<HTML
<div class="tk-panel" data-panel-icon="fa fa-empire" var="table">
  <p>The events are available for use with plugins or when adding to the system codebase.</p>
</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }

}