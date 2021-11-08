<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace WpMatomo\WpStatistics;

use Piwik\Concurrency\Lock;
use Piwik\Concurrency\LockBackend;
use Piwik\Concurrency\LockBackend\MySqlLockBackend;
use Piwik\Config;

class ImportLock extends Lock
{
    const LOCK_TTL = 300; // lock will expire 5 minutes after inactivity
    const IMPORT_LOCK_NAME = 'GoogleAnalyticsImport_importLock';

    private $configuredLockTtl;

    public function __construct(Config $config)
    {
        $this->configuredLockTtl = self::getLockTtlConfig($config);
        parent::__construct(new MySqlLockBackend(), self::IMPORT_LOCK_NAME, $this->configuredLockTtl);
    }

    public function acquireLock($id, $ttlInSeconds = 60)
    {
        return parent::acquireLock($id, $this->configuredLockTtl);
    }

    public static function getLockTtlConfig(Config $config)
    {
        $section = $config->GoogleAnalyticsImporter;
        $ttl = !empty($section['import_job_lock_ttl']) ? (int)$section['import_job_lock_ttl'] : null;
        $ttl = $ttl ?: self::LOCK_TTL;
        return $ttl;
    }
}