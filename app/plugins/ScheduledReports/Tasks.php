<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\ScheduledReports;

use Piwik\Scheduler\Schedule\Schedule;
class Tasks extends \Piwik\Plugin\Tasks
{
    public function schedule()
    {
        foreach (\Piwik\Plugins\ScheduledReports\API::getInstance()->getReports() as $report) {
            if (!$report['deleted'] && $report['period'] != Schedule::PERIOD_NEVER) {
                if (\Piwik\Plugins\ScheduledReports\ReportSchedule::isScheduledOnSiteCalendar((string) $report['period'])) {
                    // keep the delivery on the site's local Monday / first day of the month
                    $schedule = new \Piwik\Plugins\ScheduledReports\ReportSchedule((string) $report['period'], (int) $report['idsite']);
                } else {
                    $schedule = Schedule::getScheduledTimeForPeriod($report['period']);
                    $schedule->setTimezone('UTC');
                }
                $schedule->setHour($report['hour']);
                // saved hour is UTC always
                $this->custom(\Piwik\Plugins\ScheduledReports\API::getInstance(), 'sendReport', $report['idreport'], $schedule);
            }
        }
    }
}
