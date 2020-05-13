<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Updates;

use Piwik\Updater;
use Piwik\Updates as PiwikUpdates;
use WpMatomo\Logger;

class Updates_3_13_6_b1 extends PiwikUpdates
{
    public function doUpdate(Updater $updater)
    {
	    global $wpdb;

	    if ($wpdb->charset === 'utf8mb4') {
		    $db_settings = new \WpMatomo\Db\Settings();
		    $installed_tables = $db_settings->get_installed_matomo_tables();
		    if (!empty($installed_tables)) {
			    foreach ($installed_tables as $installed_table) {
			    	try {
					    $wpdb->query('ALTER TABLE `'.$installed_table.'` CONVERT TO CHARACTER SET utf8mb4');
				    } catch (\Exception $e) {
						$logger = new Logger();
						$logger->log("Failed to convert character set: " . $e->getMessage());
				    }
			    }
		    }
	    }
    }

}