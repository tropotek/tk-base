<?php
namespace Bs\Db;

use Tk\Db\Map\Model;
use Bs\Event\DbEvent;
use Bs\DbEvents;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2016 Michael Mifsud
 */
abstract class Mapper extends \Tk\Db\Mapper
{

    /***
     * @var \Tk\Event\Dispatcher
     */
    protected $dispatcher = null;


    /**
     * @param \Tk\Db\Pdo|null $db
     * @throws \Exception
     */
    public function __construct($db = null)
    {
        $this->dispatcher = $this->getConfig()->getEventDispatcher();
        parent::__construct($db);
        $this->setMarkDeleted('del');           // Default to have a del field (This will only mark the record deleted)
    }

    /**
     * Insert
     *
     * @param Model $obj
     * @return int Returns the new insert id
     */
    public function insert($obj)
    {
        $stop = false;
        if ($this->getDispatcher()) {
            $e = new DbEvent($obj, $this);
            $this->getDispatcher()->dispatch(DbEvents::MODEL_INSERT, $e);
            $stop = $e->isQueryStopped();
        }
        if (!$stop) {
            $r = parent::insert($obj);
            return $r;
        }
        return 0;
    }

    /**
     *
     * @param Model $obj
     * @return int
     */
    public function update($obj)
    {
        $stop = false;
        if ($this->getDispatcher()) {
            $e = new DbEvent($obj, $this);
            $this->getDispatcher()->dispatch(DbEvents::MODEL_UPDATE, $e);
            $stop = $e->isQueryStopped();
        }
        if (!$stop) {
            $r = parent::update($obj);
            return $r;
        }
        return 0;
    }

    /**
     * Save the object, let the code decide weather to insert or update the db.
     *
     * @param Model $obj
     * @throws \Exception
     */
    public function save($obj)
    {
        $stop = false;
        if ($this->getDispatcher()) {
            $e = new DbEvent($obj, $this);
            $this->getDispatcher()->dispatch(DbEvents::MODEL_SAVE, $e);
            $stop = $e->isQueryStopped();
        }
        if (!$stop) {
            parent::save($obj);
        }
    }

    /**
     * Delete object
     *
     * @param Model $obj
     * @return int
     */
    public function delete($obj)
    {
        $stop = false;
        if ($this->getDispatcher()) {
            $e = new DbEvent($obj, $this);
            $this->getDispatcher()->dispatch(DbEvents::MODEL_DELETE, $e);
            $stop = $e->isQueryStopped();
        }
        if (!$stop) {
            $r = parent::delete($obj);
            return $r;
        }
        return 0;
    }

    /**
     * @return \Tk\Event\Dispatcher
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * @param \Tk\Event\Dispatcher $dispatcher
     * @return $this
     */
    public function setDispatcher($dispatcher)
    {
        $this->dispatcher = $dispatcher;
        return $this;
    }

    /**
     * @return \Bs\Config
     */
    public function getConfig()
    {
        return \Bs\Config::getInstance();
    }


    /**
     * This function creates a temporary table filled with dates
     * This can be used in join querys for stats queries and ensures uniform date results
     * even if there is no data on that date.
     * <code>
     *   SELECT calDay.date AS DATE, SUM(orders.quantity) AS total_sales
     *     FROM orders RIGHT JOIN calDay ON (DATE(orders.order_date) = calDay.date)
     *   GROUP BY DATE
     *
     * -- OR
     *
     * SELECT DATE($cal.`date`) as 'date', IFNULL(count($tbl.`id`), 0) as 'total'
     * FROM `$tbl` RIGHT JOIN `$cal` ON (DATE($tbl.`created`) = DATE($cal.`date`) )
     * WHERE ($cal.`date`
     *     BETWEEN (SELECT MIN(DATE(`created`)) FROM `$tbl`)
     *         AND (SELECT MAX(DATE(`created`)) FROM `$tbl`)
     * )
     * GROUP BY `date`
     *
     * </code>
     *
     * For interval info see ADDDATE() in the Mysql Manual.
     * @see http://dev.mysql.com/doc/refman/5.6/en/date-and-time-functions.html#function_date-add
     *
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param string $tableName
     * @param string $interval
     * @throws \Tk\Db\Exception
     * @see http://www.richnetapps.com/using-mysql-generate-daily-sales-reports-filled-gaps/
     *
     * @TODO: _not_working_because_of_temp_table
     * @TODO: we need to use CONCAT() see https://forums.mysql.com/read.php?98,495352
     */
    public function createDateTable_bugger(\DateTime $dateFrom, \DateTime $dateTo, $tableName = 'temp_cal', $interval = '1 DAY')
    {
        $df = $dateFrom->format('Y-m-d');
        $dt = $dateTo->format('Y-m-d');

        $sql = <<<SQL
DROP PROCEDURE IF EXISTS procFillCal;

-- DELIMITER //

CREATE PROCEDURE procFillCal(pTableName VARCHAR(32), pStartDate DATE, pEndDate DATE, pInterval VARCHAR(8), pIntervalUnit INTEGER)
BEGIN
  DECLARE pDate DATE;
  
  -- This will not work for external use I think???????? Cannot find the table when used in an external query
--  DROP TEMPORARY TABLE IF EXISTS pTableName;
  CREATE TEMPORARY TABLE pTableName (`date` DATE );
  TRUNCATE pTableName;

  SET pDate = pStartDate;
  WHILE pDate < pEndDate DO
    
    INSERT INTO pTableName VALUES(pDate);
    
    CASE UPPER(pInterval)
      WHEN 'DAY' THEN SET pDate = ADDDATE(pDate, INTERVAL pIntervalUnit DAY);
      WHEN 'WEEK' THEN SET pDate = ADDDATE(pDate, INTERVAL pIntervalUnit WEEK);
      WHEN 'MONTH' THEN SET pDate = ADDDATE(pDate, INTERVAL pIntervalUnit MONTH);
      WHEN 'YEAR' THEN SET pDate = ADDDATE(pDate, INTERVAL pIntervalUnit YEAR);
    END CASE;
    
  END WHILE;
  
 END;
-- DELIMITER ;
SQL;
        $st = $this->getDb()->prepare($sql);
        $st->execute();

        list($iUnit, $iType) = explode(' ', $interval);

        $st = $this->getDb()->prepare('CALL procFillCal(?, ?, ?, ?, ?)');

        $st->execute(array($tableName, $df, $dt, $iType, $iUnit));

        vd($st->getPdo()->getLastQuery());
        vd($st->getPdo()->errorInfo());

    }



    /**
     * This function creates a temporary table filled with dates
     * This can be used in join querys for stats queries and ensures uniform date results
     * even if there is no data on that date.
     * <code>
     *   SELECT calDay.date AS DATE, SUM(orders.quantity) AS total_sales
     *     FROM orders RIGHT JOIN calDay ON (DATE(orders.order_date) = calDay.date)
     *   GROUP BY DATE
     *
     * -- OR
     *
     * SELECT DATE($cal.`date`) as 'date', IFNULL(count($tbl.`id`), 0) as 'total'
     * FROM `$tbl` RIGHT JOIN `$cal` ON (DATE($tbl.`created`) = DATE($cal.`date`) )
     * WHERE ($cal.`date`
     *     BETWEEN (SELECT MIN(DATE(`created`)) FROM `$tbl`)
     *         AND (SELECT MAX(DATE(`created`)) FROM `$tbl`)
     * )
     * GROUP BY `date`
     *
     * </code>
     *
     * For interval info see ADDDATE() in the Mysql Manual.
     * @see http://dev.mysql.com/doc/refman/5.6/en/date-and-time-functions.html#function_date-add
     *
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param string $tableName
     * @param string $interval
     * @throws \Tk\Db\Exception
     * @see http://www.richnetapps.com/using-mysql-generate-daily-sales-reports-filled-gaps/
     *
     * @TODO: We need a better way to handle this, temp tables generate too many issues with multiple sessions.
     * @TODO: One solution may be to creat a global lookup _cal_day, _cal_month table??????
     */
    public function createDateTable(\DateTime $dateFrom, \DateTime $dateTo, $tableName = 'calDay', $interval = '1 DAY')
    {
        $df = $dateFrom->format('Y-m-d');
        $dt = $dateTo->format('Y-m-d');

        $sql = <<<SQL
DROP TEMPORARY TABLE IF EXISTS `$tableName`;
CREATE TEMPORARY TABLE `$tableName` (`date` DATE, `year` INT, `month` INT, `day` INT);
DROP PROCEDURE IF EXISTS `fill_calendar`;
SQL;
        $this->getDb()->exec($sql);

        $sql = <<<SQL
CREATE PROCEDURE fill_calendar(start_date DATE, end_date DATE)
BEGIN
  DECLARE crt_date DATE;
  SET crt_date=start_date;
  WHILE crt_date < end_date DO
    INSERT INTO `$tableName` VALUES(crt_date, YEAR(crt_date), MONTH(crt_date), DAY(crt_date));
    SET crt_date = ADDDATE(crt_date, INTERVAL $interval);
  END WHILE;
END
SQL;
        $st = $this->getDb()->prepare($sql);
        $st->execute();

        $st = $this->getDb()->prepare('CALL fill_calendar(?, ?)');
        $st->execute(array($df, $dt));
    }

}