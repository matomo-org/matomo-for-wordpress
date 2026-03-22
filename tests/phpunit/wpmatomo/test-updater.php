<?php
/**
 * @package matomo
 */

use WpMatomo\Settings;
use WpMatomo\Updater;
use WpMatomo\User;

class UpdaterTest extends MatomoAnalytics_TestCase {


	/**
	 * @var User
	 */
	private $updater;

	public function setUp(): void {
		global $wpdb;

		$this->overwrite_wpdb      = false;
		$this->disable_temp_tables = true;

		parent::setUp();

		$wpdb->show_errors( false );

		$this->updater = new Updater( new Settings() );
	}

	public function test_lock_unlock() {
		$this->assertFalse( $this->updater->is_upgrade_in_progress() );

		$locked = Updater::lock();
		$this->assertTrue( $locked );

		$this->assertTrue( $this->updater->is_upgrade_in_progress() );

		// cannot lock it again
		$locked = Updater::lock();
		$this->assertFalse( $locked );

		// still in progress
		$this->assertTrue( $this->updater->is_upgrade_in_progress() );

		// when unlocking then we can lock it again
		$unlocked = Updater::unlock();
		$this->assertTrue( $unlocked );

		// cannot unlock again
		$unlocked = Updater::unlock();
		$this->assertFalse( $unlocked );

		// not in progress anymore
		$this->assertFalse( $this->updater->is_upgrade_in_progress() );

		// can lock it again now
		$locked = Updater::lock();
		$this->assertTrue( $locked );
		// make sure to unlock
		Updater::unlock();
	}

	public function test_load_plugin_functions_should_always_work() {
		$this->assertTrue( $this->updater->load_plugin_functions() );
	}

	public function test_default_has_no_outstanding_plugin_updates() {
		$required_updates = $this->updater->get_plugins_requiring_update();

		$plugin_data = get_plugin_data( MATOMO_ANALYTICS_FILE, $markup = false, $translate = false );
		$this->assertSame( array( 'matomo-plugin-version-matomo' => $plugin_data['Version'] ), $required_updates );
	}

	public function test_update_if_needed() {
		$this->assertNotEmpty( $this->updater->get_plugins_requiring_update() );

		$keys = $this->updater->update_if_needed();
		$this->assertSame( array( 'matomo-plugin-version-matomo' ), $keys );

		$plugin_data = get_plugin_data( MATOMO_ANALYTICS_FILE, $markup = false, $translate = false );
		$this->assertSame( $plugin_data['Version'], get_option( $keys[0] ) );

		$this->assertSame( array(), $this->updater->get_plugins_requiring_update() );

		// does not execute the update again
		$keys = $this->updater->update_if_needed();
		$this->assertSame( array(), $keys );
	}

	public function test_update_does_not_fail() {
		$this->updater->update();
		// for the phpunit warning
		$this->assertTrue( true );
	}

	public function test_update_converts_row_format_if_row_too_small_to_fit_new_dimension() {
		$dimensions_to_remove = [
			'pageviews_before',
			'config_device_model',
			'config_device_brand',
		];

		$this->remove_log_conversion_dimensions( $dimensions_to_remove );
		$this->set_log_conversion_row_format( 'Compact' );

		$row_format = $this->get_log_conversion_row_format();
		$this->assertEquals( 'Compact', $row_format ); // sanity check

		$this->updater->update();

		$this->assert_columns_exist( 'log_conversion', $dimensions_to_remove );

		$row_format = $this->get_log_conversion_row_format();
		$this->assertEquals( 'Dynamic', $row_format );
	}

	public function test_update_throws_if_update_from_version_is_not_a_semantic_version() {
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Invalid version. Please specify a full version identifier' );

		$this->updater->update( 'alskdfjsladkfj' );
	}

	public function test_update_throws_if_update_from_version_is_later_than_current_version() {
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Invalid version. The given version is greater than the current Matomo version.' );

		$this->updater->update( '99.99.99' );
	}

	public function test_update_from_version_applys_updates_from_specified_version_to_current() {
		\Piwik\Plugin\Manager::getInstance()->activatePlugin( 'TagManager' );

		$settings  = new Settings();
		$installer = new \WpMatomo\Installer( $settings );
		$installer->install();

		// remove a column that's added in the 5.0.0-b1 migration
		\Piwik\Db::exec( 'ALTER TABLE ' . \Piwik\Common::prefixTable( 'log_conversion' ) . ' DROP COLUMN pageviews_before' );
		// remove a column that's added in a TagManager migration
		\Piwik\Db::exec( 'ALTER TABLE ' . \Piwik\Common::prefixTable( 'tagmanager_container' ) . ' DROP COLUMN ignoreGtmDataLayer' );

		$columns = $this->get_columns_for( 'log_conversion' );
		$this->assertNotContains( 'pageviews_before', $columns );

		$columns = $this->get_columns_for( 'tagmanager_container' );
		$this->assertNotContains( 'ignoreGtmDataLayer', $columns );

		$this->updater->update( '4.12.0' );

		$columns = $this->get_columns_for( 'log_conversion' );
		$this->assertContains( 'pageviews_before', $columns );

		$columns = $this->get_columns_for( 'tagmanager_container' );
		$this->assertContains( 'ignoreGtmDataLayer', $columns );
	}

	private function remove_log_conversion_dimensions( $dimensions_to_remove ) {
		// remove columns
		$statements = [];
		foreach ( $dimensions_to_remove as $dimension ) {
			$statements[] = "DROP COLUMN `$dimension`";
		}
		$sql = 'ALTER TABLE ' . \Piwik\Common::prefixTable( 'log_conversion' ) . ' ' . implode( ', ', $statements );
		\Piwik\Db::exec( $sql );

		// remove option table entries
		$conditions = [];
		foreach ( $dimensions_to_remove as $dimension ) {
			$conditions[] = "option_name LIKE '%$dimension%'";
		}
		$sql = 'DELETE FROM ' . \Piwik\Common::prefixTable( 'option' ) . ' WHERE ' . implode( ' OR ', $conditions );
		\Piwik\Db::exec( $sql );
	}

	private function set_log_conversion_row_format( $row_format ) {
		$sql = 'ALTER TABLE ' . \Piwik\Common::prefixTable( 'log_conversion' ) . ' ROW_FORMAT=' . $row_format;
		\Piwik\Db::exec( $sql );
	}

	private function get_log_conversion_row_format() {
		$log_conversion = \Piwik\Common::prefixTable( 'log_conversion' );
		$sql            = "SELECT row_format FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '$log_conversion'";
		$format         = \Piwik\Db::fetchOne( $sql );
		if ( empty( $format ) ) {
			throw new \Exception( 'matomo not installed in test' );
		}
		return $format;
	}

	private function assert_columns_exist( $table, $columns ) {
		$existing_columns = \Piwik\Db::fetchAll( 'SHOW COLUMNS IN ' . \Piwik\Common::prefixTable( $table ) );
		$existing_columns = array_column( $existing_columns, 'Field' );

		$missing_columns = array_diff( $columns, $existing_columns );
		$this->assertEquals( [], $missing_columns, 'Found missing columns' );
	}

	private function get_columns_for( $table_name ) {
		$columns = \Piwik\Db::fetchAll( 'SHOW COLUMNS IN ' . \Piwik\Common::prefixTable( $table_name ) );
		$columns = array_column( $columns, 'Field' );
		return $columns;
	}
}
