<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Scheduler\Schedule;

use Exception;
/**
 * Hourly class is used to schedule tasks every hour.
 *
 * @see \Piwik\Scheduler\Task
 */
class Hourly extends \Piwik\Scheduler\Schedule\Schedule
{
    /**
     * @see ScheduledTime::getRescheduledTime
     * @return int
     */
    public function getRescheduledTime()
    {
        $currentTime = $this->getTime();
        // Adds one hour and reset the number of minutes
        $rescheduledTime = mktime(date('H', $currentTime) + 1, 0, date('s', $currentTime), date('n', $currentTime), date('j', $currentTime), date('Y', $currentTime));
        return $rescheduledTime;
    }
    /**
     * @see ScheduledTime::setHour
     * @param int $_hour
     * @throws \Exception
     * @return int
     */
    public function setHour($_hour)
    {
        throw new Exception("Method not supported");
    }
    /**
     * @see ScheduledTime::setDay
     * @param int $_day
     * @throws \Exception
     * @return int
     */
    public function setDay($_day)
    {
        throw new Exception("Method not supported");
    }
}
