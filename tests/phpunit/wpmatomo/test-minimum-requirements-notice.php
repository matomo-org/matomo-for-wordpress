<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

use WpMatomo\MinimumRequirements;
use WpMatomo\MinimumRequirementsNotice;

/**
 * phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash
 * phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
 */
class MinimumRequirementsNoticeTest extends MatomoUnit_TestCase {

	const UNMET_MESSAGE = 'MySQL 8.0 or higher is required (you are currently using MySQL 5.7.40).';

	public function setUp(): void {
		parent::setUp();

		unset( $_GET['page'] );
	}

	public function tearDown(): void {
		unset( $_GET['page'] );

		parent::tearDown();
	}

	public function test_check_requirements_outputs_nothing_for_non_admin_user() {
		wp_set_current_user( 0 );
		$_GET['page'] = 'matomo-systemreport';

		$this->assertSame( '', $this->capture_notice( $this->make_notice() ) );
	}

	public function test_check_requirements_outputs_nothing_when_requirements_are_met() {
		$this->create_set_super_admin();
		$_GET['page'] = 'matomo-systemreport';

		$this->assertSame( '', $this->capture_notice( $this->make_notice( [] ) ) );
	}

	/**
	 * @dataProvider get_always_visible_screens
	 */
	public function test_check_requirements_shows_a_notice_that_cannot_be_dismissed_where_matomo_or_plugins_are_managed( $screen, $page ) {
		$this->create_set_super_admin();
		$this->go_to_admin_screen( $screen, $page );

		$output = $this->capture_notice( $this->make_notice() );

		$this->assertStringContainsString( 'id="matomo-minimumrequirements"', $output );
		$this->assertStringContainsString( self::UNMET_MESSAGE, $output );
		$this->assertStringNotContainsString( 'is-dismissible', $output );
	}

	/**
	 * @dataProvider get_always_visible_screens
	 */
	public function test_check_requirements_ignores_a_dismissal_where_matomo_or_plugins_are_managed( $screen, $page ) {
		$this->create_set_super_admin_who_dismissed_the_notice();
		$this->go_to_admin_screen( $screen, $page );

		$output = $this->capture_notice( $this->make_notice() );

		$this->assertStringContainsString( 'id="matomo-minimumrequirements"', $output );
		$this->assertStringNotContainsString( 'is-dismissible', $output );
	}

	public function get_always_visible_screens() {
		return [
			// a matomo admin page is detected by the page query parameter, not the screen
			[ 'edit.php', 'matomo-systemreport' ],
			[ 'plugins', null ],
			[ 'plugin-install', null ],
			[ 'update-core', null ],
		];
	}

	/**
	 * @dataProvider get_other_admin_screens
	 */
	public function test_check_requirements_does_not_warn_about_upcoming_requirements_on_other_admin_pages( $screen ) {
		$this->create_set_super_admin();
		$this->go_to_admin_screen( $screen );

		// a requirements change that has not happened yet is only relevant where matomo or
		// plugins are managed
		$this->assertSame( '', $this->capture_notice( $this->make_notice() ) );
	}

	public function get_other_admin_screens() {
		return [
			[ 'dashboard' ],
			[ 'edit.php' ],
			[ 'options-general' ],
			[ 'users' ],
		];
	}

	public function test_check_requirements_shows_the_blocked_notice_when_the_installed_version_needs_the_new_requirements() {
		$this->create_set_super_admin();
		$_GET['page'] = 'matomo-systemreport';

		$notice = $this->make_notice( null, MinimumRequirements::ENFORCED_FROM_VERSION );
		$output = $this->capture_notice( $notice );

		$this->assertStringContainsString( 'id="matomo-minimumrequirementsblocked"', $output );
		$this->assertStringContainsString( 'notice-error', $output );
		$this->assertStringContainsString( 'Matomo Analytics has been disabled.', $output );
		$this->assertStringContainsString( self::UNMET_MESSAGE, $output );
		$this->assertStringContainsString( MinimumRequirementsNotice::REQUIREMENTS_FAQ_URL, $output );

		// the upcoming requirements warning must not be shown as well
		$this->assertStringNotContainsString( 'id="matomo-minimumrequirements"', $output );
		$this->assertStringNotContainsString( 'is-dismissible', $output );
	}

	public function test_check_requirements_blocked_notice_can_be_dismissed_on_other_admin_pages() {
		$this->create_set_super_admin();
		$this->go_to_admin_screen( 'edit.php' );

		$output = $this->capture_notice( $this->make_notice( null, MinimumRequirements::ENFORCED_FROM_VERSION ) );

		$this->assertStringContainsString( 'id="matomo-minimumrequirementsblocked"', $output );
		$this->assertStringContainsString( MinimumRequirementsNotice::NOTICE_CLASS, $output );
		$this->assertStringContainsString( 'is-dismissible', $output );
	}

	public function test_check_requirements_blocked_notice_respects_a_dismissal_on_other_admin_pages() {
		$this->create_set_super_admin_who_dismissed_the_notice();
		$this->go_to_admin_screen( 'edit.php' );

		$notice = $this->make_notice( null, MinimumRequirements::ENFORCED_FROM_VERSION );
		$this->assertSame( '', $this->capture_notice( $notice ) );
	}

	public function test_check_requirements_blocked_notice_cannot_be_dismissed_on_the_plugins_page() {
		$this->create_set_super_admin_who_dismissed_the_notice();
		$this->go_to_admin_screen( 'plugins' );

		$output = $this->capture_notice( $this->make_notice( null, MinimumRequirements::ENFORCED_FROM_VERSION ) );

		$this->assertStringContainsString( 'id="matomo-minimumrequirementsblocked"', $output );
		$this->assertStringNotContainsString( 'is-dismissible', $output );
	}

	public function test_check_requirements_outputs_nothing_when_the_installed_version_can_run_here() {
		$this->create_set_super_admin();
		$this->go_to_admin_screen( 'edit.php' );

		$this->assertSame( '', $this->capture_notice( $this->make_notice( [], MinimumRequirements::ENFORCED_FROM_VERSION ) ) );
	}

	private function go_to_admin_screen( $screen, $page = null ) {
		if ( $page ) {
			$_GET['page'] = $page;
		} else {
			unset( $_GET['page'] );
		}

		set_current_screen( $screen );
	}

	private function create_set_super_admin_who_dismissed_the_notice() {
		$user_id = $this->create_set_super_admin();
		update_user_meta( $user_id, MinimumRequirementsNotice::OPTION_NAME_MINIMUM_REQUIREMENTS_DISMISSED, true );
		return $user_id;
	}

	/**
	 * @param string[]|null $unmet          what the requirements check should report, defaults to
	 *                                      an unsupported MySQL version.
	 * @param string        $plugin_version the version of the plugin that is installed
	 */
	private function make_notice( $unmet = null, $plugin_version = '5.12.2' ) {
		$requirements             = new class() extends MinimumRequirements {
			public $test_unmet = [];

			public function get_unmet_requirements( $php_version = PHP_VERSION ) {
				return $this->test_unmet;
			}
		};
		$requirements->test_unmet = null === $unmet ? [ self::UNMET_MESSAGE ] : $unmet;

		$notice                      = new class( $requirements ) extends MinimumRequirementsNotice {
			public $test_plugin_version = '5.12.2';

			protected function get_plugin_version() {
				return $this->test_plugin_version;
			}
		};
		$notice->test_plugin_version = $plugin_version;

		return $notice;
	}

	private function capture_notice( MinimumRequirementsNotice $notice ) {
		ob_start();
		$notice->check_requirements();
		return ob_get_clean();
	}
}
