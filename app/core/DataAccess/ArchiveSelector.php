<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\DataAccess;

use Exception;
use Piwik\Archive;
use Piwik\Archive\Chunk;
use Piwik\ArchiveProcessor;
use Piwik\ArchiveProcessor\Rules;
use Piwik\Common;
use Piwik\Date;
use Piwik\Db;
use Piwik\Period;
use Piwik\Period\Range;
use Piwik\Segment;

/**
 * Data Access object used to query archives
 *
 * A record in the Database for a given report is defined by
 * - idarchive     = unique ID that is associated to all the data of this archive (idsite+period+date)
 * - idsite        = the ID of the website
 * - date1         = starting day of the period
 * - date2         = ending day of the period
 * - period        = integer that defines the period (day/week/etc.). @see period::getId()
 * - ts_archived   = timestamp when the archive was processed (UTC)
 * - name          = the name of the report (ex: uniq_visitors or search_keywords_by_search_engines)
 * - value         = the actual data (a numeric value, or a blob of compressed serialized data)
 *
 */
class ArchiveSelector
{
    const NB_VISITS_RECORD_LOOKED_UP = "nb_visits";

    const NB_VISITS_CONVERTED_RECORD_LOOKED_UP = "nb_visits_converted";

    private static function getModel()
    {
        return new Model();
    }

    /**
     * @param ArchiveProcessor\Parameters $params
     * @param bool $minDatetimeArchiveProcessedUTC deprecated. will be removed in Matomo 4.
     * @return array|bool
     * @throws Exception
     */
    public static function getArchiveIdAndVisits(ArchiveProcessor\Parameters $params, $minDatetimeArchiveProcessedUTC = false)
    {
        $idSite       = $params->getSite()->getId();
        $period       = $params->getPeriod()->getId();
        $dateStart    = $params->getPeriod()->getDateStart();
        $dateStartIso = $dateStart->toString('Y-m-d');
        $dateEndIso   = $params->getPeriod()->getDateEnd()->toString('Y-m-d');

        $numericTable = ArchiveTableCreator::getNumericTable($dateStart);

        $requestedPlugin = $params->getRequestedPlugin();
        $segment         = $params->getSegment();
        $plugins = array("VisitsSummary", $requestedPlugin);

        $doneFlags      = Rules::getDoneFlags($plugins, $segment);
        $doneFlagValues = Rules::getSelectableDoneFlagValues();

        $results = self::getModel()->getArchiveIdAndVisits($numericTable, $idSite, $period, $dateStartIso, $dateEndIso, $doneFlags, $doneFlagValues);

        if (empty($results)) {
            return false;
        }

        $idArchive = self::getMostRecentIdArchiveFromResults($segment, $requestedPlugin, $results);

        $idArchiveVisitsSummary = self::getMostRecentIdArchiveFromResults($segment, "VisitsSummary", $results);

        list($visits, $visitsConverted) = self::getVisitsMetricsFromResults($idArchive, $idArchiveVisitsSummary, $results);

        if (false === $visits && false === $idArchive) {
            return false;
        }

        return array($idArchive, $visits, $visitsConverted);
    }

    protected static function getVisitsMetricsFromResults($idArchive, $idArchiveVisitsSummary, $results)
    {
        $visits = $visitsConverted = false;
        $archiveWithVisitsMetricsWasFound = ($idArchiveVisitsSummary !== false);

        if ($archiveWithVisitsMetricsWasFound) {
            $visits = $visitsConverted = 0;
        }

        foreach ($results as $result) {
            if (in_array($result['idarchive'], array($idArchive, $idArchiveVisitsSummary))) {
                $value = (int)$result['value'];
                if (empty($visits)
                    && $result['name'] == self::NB_VISITS_RECORD_LOOKED_UP
                ) {
                    $visits = $value;
                }
                if (empty($visitsConverted)
                    && $result['name'] == self::NB_VISITS_CONVERTED_RECORD_LOOKED_UP
                ) {
                    $visitsConverted = $value;
                }
            }
        }

        return array($visits, $visitsConverted);
    }

    protected static function getMostRecentIdArchiveFromResults(Segment $segment, $requestedPlugin, $results)
    {
        $idArchive = false;
        $namesRequestedPlugin = Rules::getDoneFlags(array($requestedPlugin), $segment);

        foreach ($results as $result) {
            if ($idArchive === false
                && in_array($result['name'], $namesRequestedPlugin)
            ) {
                $idArchive = $result['idarchive'];
                break;
            }
        }

        return $idArchive;
    }

    /**
     * Queries and returns archive IDs for a set of sites, periods, and a segment.
     *
     * @param array $siteIds
     * @param array $periods
     * @param Segment $segment
     * @param array $plugins List of plugin names for which data is being requested.
     * @return array Archive IDs are grouped by archive name and period range, ie,
     *               array(
     *                   'VisitsSummary.done' => array(
     *                       '2010-01-01' => array(1,2,3)
     *                   )
     *               )
     * @throws
     */
    public static function getArchiveIds($siteIds, $periods, $segment, $plugins)
    {
        if (empty($siteIds)) {
            throw new \Exception("Website IDs could not be read from the request, ie. idSite=");
        }

        foreach ($siteIds as $index => $siteId) {
            $siteIds[$index] = (int) $siteId;
        }

        $getArchiveIdsSql = "SELECT idsite, name, date1, date2, MAX(idarchive) as idarchive
                               FROM %s
                              WHERE idsite IN (" . implode(',', $siteIds) . ")
                                AND " . self::getNameCondition($plugins, $segment) . "
                                AND %s
                           GROUP BY idsite, date1, date2, name";

        $monthToPeriods = array();
        foreach ($periods as $period) {
            /** @var Period $period */
            if ($period->getDateStart()->isLater(Date::now()->addDay(2))) {
                continue; // avoid creating any archive tables in the future
            }
            $table = ArchiveTableCreator::getNumericTable($period->getDateStart());
            $monthToPeriods[$table][] = $period;
        }

        $db = Db::get();

        // for every month within the archive query, select from numeric table
        $result = array();
        foreach ($monthToPeriods as $table => $periods) {
            $firstPeriod = reset($periods);

            $bind = array();

            if ($firstPeriod instanceof Range) {
                $dateCondition = "date1 = ? AND date2 = ?";
                $bind[] = $firstPeriod->getDateStart()->toString('Y-m-d');
                $bind[] = $firstPeriod->getDateEnd()->toString('Y-m-d');
            } else {
                // we assume there is no range date in $periods
                $dateCondition = '(';

                foreach ($periods as $period) {
                    if (strlen($dateCondition) > 1) {
                        $dateCondition .= ' OR ';
                    }

                    $dateCondition .= "(period = ? AND date1 = ? AND date2 = ?)";
                    $bind[] = $period->getId();
                    $bind[] = $period->getDateStart()->toString('Y-m-d');
                    $bind[] = $period->getDateEnd()->toString('Y-m-d');
                }

                $dateCondition .= ')';
            }

            $sql = sprintf($getArchiveIdsSql, $table, $dateCondition);


            $archiveIds = $db->fetchAll($sql, $bind);

            // get the archive IDs
            foreach ($archiveIds as $row) {
                //FIXMEA duplicate with Archive.php
                $dateStr = $row['date1'] . ',' . $row['date2'];

                $result[$row['name']][$dateStr][] = $row['idarchive'];
            }
        }

        return $result;
    }

    /**
     * Queries and returns archive data using a set of archive IDs.
     *
     * @param array $archiveIds The IDs of the archives to get data from.
     * @param array $recordNames The names of the data to retrieve (ie, nb_visits, nb_actions, etc.).
     *                           Note: You CANNOT pass multiple recordnames if $loadAllSubtables=true.
     * @param string $archiveDataType The archive data type (either, 'blob' or 'numeric').
     * @param int|null|string $idSubtable  null if the root blob should be loaded, an integer if a subtable should be
     *                                     loaded and 'all' if all subtables should be loaded.
     * @return array
     *@throws Exception
     */
    public static function getArchiveData($archiveIds, $recordNames, $archiveDataType, $idSubtable)
    {
        $chunk = new Chunk();

        $db = Db::get();

        // create the SQL to select archive data
        $loadAllSubtables = $idSubtable == Archive::ID_SUBTABLE_LOAD_ALL_SUBTABLES;
        if ($loadAllSubtables) {
            $name = reset($recordNames);

            // select blobs w/ name like "$name_[0-9]+" w/o using RLIKE
            $nameEnd = strlen($name) + 1;
            $nameEndAppendix = $nameEnd + 1;
            $appendix = $chunk->getAppendix();
            $lenAppendix = strlen($appendix);

            $checkForChunkBlob  = "SUBSTRING(name, $nameEnd, $lenAppendix) = '$appendix'";
            $checkForSubtableId = "(SUBSTRING(name, $nameEndAppendix, 1) >= '0'
                                    AND SUBSTRING(name, $nameEndAppendix, 1) <= '9')";

            $whereNameIs = "(name = ? OR (name LIKE ? AND ( $checkForChunkBlob OR $checkForSubtableId ) ))";
            $bind = array($name, $name . '%');
        } else {
            if ($idSubtable === null) {
                // select root table or specific record names
                $bind = array_values($recordNames);
            } else {
                // select a subtable id
                $bind = array();
                foreach ($recordNames as $recordName) {
                    // to be backwards compatibe we need to look for the exact idSubtable blob and for the chunk
                    // that stores the subtables (a chunk stores many blobs in one blob)
                    $bind[] = $chunk->getRecordNameForTableId($recordName, $idSubtable);
                    $bind[] = self::appendIdSubtable($recordName, $idSubtable);
                }
            }

            $inNames     = Common::getSqlStringFieldsArray($bind);
            $whereNameIs = "name IN ($inNames)";
        }

        $getValuesSql = "SELECT value, name, idsite, date1, date2, ts_archived
                                FROM %s
                                WHERE idarchive IN (%s)
                                  AND " . $whereNameIs;

        // get data from every table we're querying
        $rows = array();
        foreach ($archiveIds as $period => $ids) {
            if (empty($ids)) {
                throw new Exception("Unexpected: id archive not found for period '$period' '");
            }

            // $period = "2009-01-04,2009-01-04",
            $date = Date::factory(substr($period, 0, 10));

            $isNumeric = $archiveDataType == 'numeric';
            if ($isNumeric) {
                $table = ArchiveTableCreator::getNumericTable($date);
            } else {
                $table = ArchiveTableCreator::getBlobTable($date);
            }

            $sql      = sprintf($getValuesSql, $table, implode(',', $ids));
            $dataRows = $db->fetchAll($sql, $bind);

            foreach ($dataRows as $row) {
                if ($isNumeric) {
                    $rows[] = $row;
                } else {
                    $row['value'] = self::uncompress($row['value']);

                    if ($chunk->isRecordNameAChunk($row['name'])) {
                        self::moveChunkRowToRows($rows, $row, $chunk, $loadAllSubtables, $idSubtable);
                    } else {
                        $rows[] = $row;
                    }
                }
            }
        }

        return $rows;
    }

    private static function moveChunkRowToRows(&$rows, $row, Chunk $chunk, $loadAllSubtables, $idSubtable)
    {
        // $blobs = array([subtableID] = [blob of subtableId])
        $blobs = Common::safe_unserialize($row['value']);

        if (!is_array($blobs)) {
            return;
        }

        // $rawName = eg 'PluginName_ArchiveName'
        $rawName = $chunk->getRecordNameWithoutChunkAppendix($row['name']);

        if ($loadAllSubtables) {
            foreach ($blobs as $subtableId => $blob) {
                $row['value'] = $blob;
                $row['name']  = self::appendIdSubtable($rawName, $subtableId);
                $rows[] = $row;
            }
        } elseif (array_key_exists($idSubtable, $blobs)) {
            $row['value'] = $blobs[$idSubtable];
            $row['name'] = self::appendIdSubtable($rawName, $idSubtable);
            $rows[] = $row;
        }
    }

    public static function appendIdSubtable($recordName, $id)
    {
        return $recordName . "_" . $id;
    }

    private static function uncompress($data)
    {
        return @gzuncompress($data);
    }

    /**
     * Returns the SQL condition used to find successfully completed archives that
     * this instance is querying for.
     *
     * @param array $plugins
     * @param Segment $segment
     * @return string
     */
    private static function getNameCondition(array $plugins, Segment $segment)
    {
        // the flags used to tell how the archiving process for a specific archive was completed,
        // if it was completed
        $doneFlags    = Rules::getDoneFlags($plugins, $segment);
        $allDoneFlags = "'" . implode("','", $doneFlags) . "'";

        $possibleValues = Rules::getSelectableDoneFlagValues();

        // create the SQL to find archives that are DONE
        return "((name IN ($allDoneFlags)) AND (value IN (" . implode(',', $possibleValues) . ")))";
    }
}
