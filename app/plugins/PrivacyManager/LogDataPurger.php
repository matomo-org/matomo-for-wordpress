<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\PrivacyManager;

use Piwik\Common;
use Piwik\Container\StaticContainer;
use Piwik\DataAccess\RawLogDao;
use Piwik\Date;
use Piwik\Db;
use Piwik\Db\TransactionLevel;
use Piwik\Log;
use Piwik\LogDeleter;
use Piwik\Piwik;
/**
 * Purges the log_visit, log_conversion and related tables of old visit data.
 */
class LogDataPurger
{
    /**
     * The max set of rows each table scan select should query at one time.
     */
    public static $selectSegmentSize = 100000;
    /**
     * LogDeleter service used to delete visits.
     *
     * @var LogDeleter
     */
    private $logDeleter;
    /**
     * DAO class that is used to delete unused actions.
     *
     * @var RawLogDao
     */
    private $rawLogDao;
    /**
     * Constructor.
     */
    public function __construct(LogDeleter $logDeleter, RawLogDao $rawLogDao)
    {
        $this->logDeleter = $logDeleter;
        $this->rawLogDao = $rawLogDao;
    }
    /**
     * Purges old data from the following tables:
     * - log_visit
     * - log_link_visit_action
     * - log_conversion
     * - log_conversion_item
     * - log_action
     *
     * @param int $deleteLogsOlderThan The number of days after which log entries are considered old.
     *                                 Visits and related data whose age is greater than this number
     *                                 will be purged.
     * @param bool $deleteUnusedLogActions Whether to delete unused log actions or not
     */
    public function purgeData($deleteLogsOlderThan, $deleteUnusedLogActions)
    {
        $dateUpperLimit = Date::factory("today")->subDay($deleteLogsOlderThan);
        $transactionLevel = new TransactionLevel(Db::get());
        $transactionLevel->setUncommitted();
        $this->logDeleter->deleteVisitsFor($start = null, $dateUpperLimit->getDatetime());
        $transactionLevel->restorePreviousStatus();
        $logTables = self::getDeleteTableLogTables();
        // delete unused actions from the log_action table (but only if we can lock tables)
        if ($deleteUnusedLogActions) {
            if (Db::isLockPrivilegeGranted()) {
                $this->rawLogDao->deleteUnusedLogActions();
            } else {
                $logMessage = get_class($this) . ": LOCK TABLES privilege not granted; skipping unused actions purge";
                Log::warning($logMessage);
            }
        }
        /**
         * Triggered when a plugin is supposed to delete log/raw data that is older than a certain amount of days.
         *
         * **Example**
         *
         *     public function deleteLogsOlderThan($dateUpperLimit, $deleteLogsOlderThan)
         *     {
         *         Db::query('DELETE FROM mytable WHERE creation_date < ' . $dateUpperLimit->getDateTime());
         *     }
         *
         * @param \Piwik\Date $dateUpperLimit A date where visits that occur before this time should be deleted.
         * @param int $deleteLogsOlderThan The number of days after which log entries are considered old.
         *                                 Visits and related data whose age is greater than this number will be purged.
         */
        Piwik::postEvent('PrivacyManager.deleteLogsOlderThan', array($dateUpperLimit, $deleteLogsOlderThan));
        // optimize table overhead after deletion
        Db::optimizeTables($logTables);
    }
    /**
     * Returns an array describing what data would be purged if purging were invoked.
     *
     * This function returns an array that maps table names with the number of rows
     * that will be deleted.
     *
     * @param int $deleteLogsOlderThan The number of days after which log entries are considered old.
     *                                 Visits and related data whose age is greater than this number
     *                                 will be purged.
     * @return array
     *
     * TODO: purge estimate uses max idvisit w/ time, but purge does not, so estimate may be less accurate.
     *       to be more accurate, it should use the same strategy as purgeData(), but this could be very slow.
     */
    public function getPurgeEstimate($deleteLogsOlderThan)
    {
        $result = array();
        // deal w/ log tables that will be purged
        $maxIdVisit = $this->getDeleteIdVisitOffset($deleteLogsOlderThan);
        if (!empty($maxIdVisit)) {
            foreach (self::getDeleteTableLogTables() as $table) {
                // getting an estimate for log_action is not supported since it can take too long
                if ($table != Common::prefixTable('log_action')) {
                    $rowCount = $this->getLogTableDeleteCount($table, $maxIdVisit);
                    if ($rowCount > 0) {
                        $result[$table] = $rowCount;
                    }
                }
            }
        }
        return $result;
    }
    /**
     * get highest idVisit to delete rows from
     * @return string
     */
    private function getDeleteIdVisitOffset($deleteLogsOlderThan)
    {
        $logVisit = Common::prefixTable("log_visit");
        // get max idvisit
        $maxIdVisit = Db::fetchOne("SELECT MAX(idvisit) FROM {$logVisit}");
        if (empty($maxIdVisit)) {
            return false;
        }
        // select highest idvisit to delete from
        $dateStart = Date::factory("today")->subDay($deleteLogsOlderThan);
        $sql = "SELECT idvisit\n\t\t          FROM {$logVisit}\n\t\t         WHERE '" . $dateStart->toString('Y-m-d H:i:s') . "' > visit_last_action_time\n\t\t           AND idvisit <= ?\n\t\t           AND idvisit > ?\n\t\t      ORDER BY idvisit DESC\n\t\t         LIMIT 1";
        return Db::segmentedFetchFirst($sql, $maxIdVisit, 0, -self::$selectSegmentSize);
    }
    private function getLogTableDeleteCount($table, $maxIdVisit)
    {
        $sql = "SELECT COUNT(*) FROM {$table} WHERE idvisit <= ?";
        return (int) Db::fetchOne($sql, array($maxIdVisit));
    }
    // let's hardcode, since these are not dynamically created tables
    public static function getDeleteTableLogTables()
    {
        $provider = StaticContainer::get('Piwik\\Plugin\\LogTablesProvider');
        $result = array();
        foreach ($provider->getAllLogTables() as $logTable) {
            if ($logTable->getColumnToJoinOnIdVisit()) {
                $result[] = Common::prefixTable($logTable->getName());
            }
        }
        if (Db::isLockPrivilegeGranted()) {
            $result[] = Common::prefixTable('log_action');
        }
        return $result;
    }
}
