<?php
/**
 * Helpers shared by the two test cases that run against an installed Matomo,
 * MatomoAnalytics_TestCase and MatomoAnalytics_SharedFixture_TestCase.
 *
 * @package matomo
 */

use Piwik\Config;

trait MatomoAnalyticsTest {

	/**
	 * Disable creation of temporary tables. This may be needed when you're writing a test that is
	 * tracking/archiving data. Problem is with temp tables many queries fail like this
	 *
	 * Can't really use temporary tables as we otherwise get errors like
	 * : WP DB Error: Can't reopen table: 'log_action' - in plugin Actions at PluginsArchiver.php:186
	 * because temp tables cannot be joined
	 *
	 * @var bool
	 */
	protected $disable_temp_tables = false;

	/**
	 * @param string $query
	 *
	 * @return mixed
	 * phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
	 */
	public function _create_temporary_tables( $query ) {
		if ( ! $this->disable_temp_tables ) {
			$query = parent::_create_temporary_tables( $query );
		}

		return $query;
	}

	/**
	 * @param string $query
	 *
	 * @return mixed
	 * phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
	 */
	public function _drop_temporary_tables( $query ) {
		if ( ! $this->disable_temp_tables ) {
			$query = parent::_drop_temporary_tables( $query );
		}

		return $query;
	}

	protected function enable_browser_archiving() {
		$_GET['trigger']                                = 'archivephp';
		$general                                        = Config::getInstance()->General;
		$general['enable_browser_archiving_triggering'] = 1;
		$general['time_before_today_archive_considered_outdated'] = 1;
		Config::getInstance()->General                            = $general;

		$debug                            = Config::getInstance()->Debug;
		$debug['always_archive_data_day'] = 1;
		Config::getInstance()->Debug      = $debug;
	}

	protected function create_set_super_admin() {
		return ( new MatomoUnit_Matomo_Fixture() )->create_set_super_admin( self::factory() );
	}
}
