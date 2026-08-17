<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

use WpMatomo\Admin\PluginSuggestions\Suggestions\UsersFlow;

class UsersFlowTest extends MatomoAnalytics_SharedFixture_TestCase {

	public function setUp(): void {
		parent::setUp();

		$this->skip_if_old_wordpress();

		\Piwik\Config::getInstance()->General['enable_browser_archiving_triggering'] = 1;
		$this->create_set_super_admin();
	}

	public function test_should_trigger_returns_true_if_post_count_is_above_threshold() {
		$this->create_test_posts( 25 );

		$suggestion = new UsersFlow();
		$this->assertTrue( $suggestion->should_trigger() );
	}

	public function test_should_trigger_returns_false_if_post_count_is_below_threshold() {
		$this->create_test_posts( 5 );

		$suggestion = new UsersFlow();
		$this->assertFalse( $suggestion->should_trigger() );
	}

	private function create_test_posts( $post_count ) {
		for ( $i = 0; $i < $post_count; $i++ ) {
			self::factory()->post->create();
		}
	}
}
