<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo\Admin\PluginSuggestions;

use WpMatomo\Admin\Menu;
use WpMatomo\Report\Data;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

abstract class Suggestion {

	const PAST_DATA_DAY_COUNT = 30;

	/**
	 * @var string
	 */
	protected $plugin_slug = '';

	/**
	 * @var string
	 */
	protected $plugin_name = '';

	/**
	 * @var string
	 */
	protected $trigger_desc_short = '';

	/**
	 * @var string
	 */
	protected $trigger_desc_long = '';

	/**
	 * @var string
	 */
	protected $plugin_desc_short = '';

	/**
	 * @var string
	 */
	protected $plugin_desc_long = '';

	/**
	 * @var string|null
	 */
	protected $image_file = null;

	public function __construct() {
		$this->init();

		if ( empty( $this->plugin_name ) ) {
			throw new \Exception( 'SuggestionTrigger implementation must define a plugin.' );
		}
	}

	/**
	 * Note: this function will be run with temporary superuser access during cron.
	 * It should not be run at any other time.
	 *
	 * @return bool
	 */
	abstract public function should_trigger();

	abstract public function init();

	public function is_suggestion_applicable() {
		return ! $this->is_plugin_installed( $this->plugin_slug );
	}

	public function register_hooks() {
		// empty
	}

	public function get_unlock_url() {
		// phpcs:ignore WordPress.WP.CapitalPDangit.Misspelled
		return 'https://matomo.org/get/matomo-for-wordpress-' . $this->to_snake_case( $this->plugin_slug ) . '/?source=wordpress';
	}

	/**
	 * @param string $method
	 * @param int    $filter_limit
	 * @param string $sort_by_column
	 * @param array  $extra_params
	 * @return array|mixed|\Piwik\DataTable|string
	 */
	protected function get_last_month_data( $method, $filter_limit = 500, $sort_by_column = 'label', $extra_params = [] ) {
		$data_query  = new Data();
		$report_data = $data_query->fetch_raw_report(
			$method,
			'range',
			'previous' . self::PAST_DATA_DAY_COUNT,
			$sort_by_column,
			$filter_limit,
			array_merge(
				[
					'format_metrics' => 0,
				],
				$extra_params
			)
		);
		return $report_data;
	}

	protected function is_plugin_installed( $plugin_slug ) {
		return is_dir( WP_PLUGIN_DIR . '/' . $plugin_slug )
			|| is_dir( WPMU_PLUGIN_DIR . '/' . $plugin_slug );
	}

	public function get_image_url() {
		if ( ! $this->image_file ) {
			return null;
		}

		return plugins_url( '/assets/img/suggestions/' . $this->image_file, MATOMO_ANALYTICS_FILE );
	}

	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	public function get_plugin_name() {
		return $this->plugin_name;
	}

	public function get_trigger_desc_short() {
		return $this->trigger_desc_short;
	}

	public function get_trigger_desc_long() {
		return $this->trigger_desc_long;
	}

	public function get_plugin_desc_short() {
		return $this->plugin_desc_short;
	}

	public function get_plugin_desc_long() {
		return $this->plugin_desc_long;
	}

	public function get_short_id() {
		$id = get_class( $this );
		$id = explode( '\\', $id );
		$id = end( $id );
		return $id;
	}

	private function to_snake_case( $value ) {
		return strtolower( preg_replace( '/(?<!^)[A-Z]/', '-$0', $value ) );
	}
}
