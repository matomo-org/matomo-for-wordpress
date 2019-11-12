<?php
/**
 * @package matomo
 */

use WpMatomo\Admin\TrackingSettings;
use WpMatomo\Settings;

class AdminTrackingSettingsBootstrappedTest extends MatomoAnalytics_TestCase {

	/**
	 * @var TrackingSettings
	 */
	private $tracking_settings;

	public function setUp() {
		parent::setUp();

		$settings                = new Settings();
		$this->tracking_settings = new TrackingSettings( $settings );

		$this->assume_admin_page();
		$this->create_set_super_admin();

		\WpMatomo\Bootstrap::do_bootstrap();
	}

	public function test_get_active_containers_when_containers_defined() {
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			$this->markTestSkipped( 'Not running for multisite' );

			return;
		}
		$site   = new WpMatomo\Site();
		$idsite = $site->get_current_matomo_site_id();

		$id = \Piwik\API\Request::processRequest(
			'TagManager.createDefaultContainerForSite',
			array(
				'idSite' => $idsite,
			)
		);

		$containers = $this->tracking_settings->get_active_containers();
		$this->assertSame( array( $id => 'Default Container' ), $containers );
	}

}
