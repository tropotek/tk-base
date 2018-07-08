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

        $this->table->addCell(new \Tk\Table\Cell\Text('name'));
        $this->table->addCell(new \Tk\Table\Cell\Text('value'));
        $this->table->addCell(new \Tk\Table\Cell\Text('eventClass'));
        $this->table->addCell(new \Tk\Table\Cell\Html('doc'))->addCss('key');

        $this->table->addAction(\Tk\Table\Action\Csv::create());

        $list = $this->convertEventData(\App\Config::getInstance()->getEventDispatcher()->getAvailableEvents(\App\Config::getInstance()->getSitePath()));
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

        $template->replaceTemplate('table', $this->table->getRenderer()->show());

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
<div>

  <div class="panel panel-default">
    <div class="panel-heading">
      <i class="fa fa-empire fa-fw"></i> Available Events
    </div>
    <div class="panel-body">
      <p>The events are available for use with plugins or when adding to the system codebase.</p>
      <div var="table"></div>
    </div>
  </div>

</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }

}