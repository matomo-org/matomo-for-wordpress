<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Updates;

use Piwik\Updater;
use Piwik\Updates as PiwikUpdates;
use Piwik\Updater\Migration\Factory as MigrationFactory;
/**
 * Update for version 4.0.0-b3.
 */
class Updates_4_0_0_rc3 extends PiwikUpdates
{
    /**
     * @var MigrationFactory
     */
    private $migration;
    public function __construct(MigrationFactory $factory)
    {
        $this->migration = $factory;
    }
    public function getMigrations(Updater $updater)
    {
        $migrations = [];
        $migrations[] = $this->migration->db->addColumn('archive_invalidations', 'ts_started', 'DATETIME NULL');
        return $migrations;
    }
    public function doUpdate(Updater $updater)
    {
        $updater->executeMigrations(__FILE__, $this->getMigrations($updater));
    }
}
