<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

use WpMatomo\Admin\PluginSuggestions\Suggestions\Funnels;


class FunnelsTest extends MatomoAnalytics_TestCase {

	public function setUp(): void {
		parent::setUp();

		$this->skip_if_old_wordpress();
		$this->create_set_super_admin();
	}

	public function test_should_trigger_should_return_true_if_at_least_one_goal_exists() {
		\Piwik\Plugins\Goals\API::getInstance()->addGoal( 1, 'test goal', 'url', 'http', 'contains' );

		$goal_count = \Piwik\Db::fetchOne( 'SELECT COUNT(*) FROM ' . \Piwik\Common::prefixTable( 'goal' ) );
		$this->assertEquals( 1, $goal_count );

		$suggestion = new Funnels();
		$this->assertTrue( $suggestion->should_trigger() );
	}

	public function test_should_trigger_should_return_false_if_no_goals_exist() {
		$goal_count = \Piwik\Db::fetchOne( 'SELECT COUNT(*) FROM ' . \Piwik\Common::prefixTable( 'goal' ) );
		$this->assertEquals( 0, $goal_count );

		$suggestion = new Funnels();
		$this->assertFalse( $suggestion->should_trigger() );
	}
}
