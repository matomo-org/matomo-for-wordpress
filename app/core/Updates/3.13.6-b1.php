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
		    $save = $wpdb->show_errors(false);

		    $db_settings = new \WpMatomo\Db\Settings();
            $wpdb->query(sprintf('ALTER TABLE `%s` CHANGE `%s` `%s` %s',
		                $db_settings->prefix_table_name('session'),
		                'id', 'id', 'VARCHAR(191)'
		    ));
            $wpdb->query(sprintf('ALTER TABLE `%s` CHANGE `%s` `%s` %s',
		                $db_settings->prefix_table_name('site_url'),
		                'url', 'url', 'VARCHAR(190)'
		    ));
            $wpdb->query(sprintf('ALTER TABLE `%s` CHANGE `%s` `%s` %s',
		                $db_settings->prefix_table_name('option'),
		                'option_name', 'option_name', 'VARCHAR(191)'
		    ));

		    $installed_tables = $db_settings->get_installed_matomo_tables();

		    if (!empty($installed_tables)) {
			    foreach ($installed_tables as $table) {
				    if (preg_match('/archive_/', $table) == 1) {
					    $wpdb->query(sprintf('ALTER TABLE `%s` CHANGE `%s` `%s` %s',
						    $table, 'name', 'name', 'VARCHAR(190)'
					    ));
				    }
			    }

			    foreach ($installed_tables as $installed_table) {
			    	try {
					    $wpdb->query('ALTER TABLE `'.$installed_table.'` CONVERT TO CHARACTER SET utf8mb4');
				    } catch (\Exception $e) {
						$logger = new Logger();
						$logger->log("Failed to convert character set: " . $e->getMessage());
				    }
			    }
		    }
		    $wpdb->show_errors($save);
	    }
    }

}