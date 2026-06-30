<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo\Site\Sync;

use Piwik\Config as PiwikConfig;
use WpMatomo;
use WpMatomo\Bootstrap;
use WpMatomo\Logger;
use WpMatomo\ScheduledTasks;
use WpMatomo\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class SyncConfig {

	/**
	 * @var Logger
	 */
	private $logger;

	/**
	 * @var Settings
	 */
	private $settings;

	public function __construct( Settings $settings ) {
		$this->logger   = new Logger();
		$this->settings = $settings;
	}

	private function get_all() {
		$options = $this->settings->get_global_option( Settings::NETWORK_CONFIG_OPTIONS );

		if ( empty( $options ) || ! is_array( $options ) ) {
			$options = [];
		}

		return $options;
	}

	public function get_config_value( $group, $key ) {
		Bootstrap::do_bootstrap();
		$config    = PiwikConfig::getInstance();
		$the_group = $config->{$group};
		if ( ! empty( $the_group ) && isset( $the_group[ $key ] ) ) {
			return $the_group[ $key ];
		}
		return null;
	}

	public function set_config_value( $group, $key, $value ) {
		if ( $this->settings->is_network_enabled() || ! WpMatomo::is_safe_mode() ) {
			Bootstrap::do_bootstrap();
			$config    = PiwikConfig::getInstance();
			$the_group = $config->{$group};
			if ( empty( $the_group ) ) {
				$the_group = [];
			}
			$the_group[ $key ] = $value;
			$config->{$group}  = $the_group;
			$config->forceSave();

			// need to update all config files
			if ( $this->settings->is_network_enabled() ) {
				wp_schedule_single_event( time() + 5, ScheduledTasks::EVENT_SYNC );
			}
		}
	}
}
