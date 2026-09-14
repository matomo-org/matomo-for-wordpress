<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

use Piwik\Plugins\TagManager\Template\Tag\CookiebotTag;
use Piwik\Plugins\TagManager\Template\Tag\CustomHtmlTag;
use Piwik\Plugins\TagManager\Template\Tag\GoogleTagTag;
use Piwik\Plugins\TagManager\Template\Tag\HotjarTag;
use Piwik\Plugins\TagManager\Template\Tag\MatomoTag;
use Piwik\Plugins\TagManager\Template\Tag\ThemeColorTag;
use Piwik\Plugins\TagManager\Template\Tag\VisualWebsiteOptimizerTag;
use Piwik\Plugins\WordPress\Overrides\TagManager\BlockedTemplates;
use Piwik\Plugins\WordPress\Overrides\TagManager\SecuredTemplateConstraints;
use Piwik\Validators\Exception as ValidatorException;
use WpMatomo\Roles;

/**
 * @package matomo
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 */
class BlockedTemplatesTest extends MatomoAnalytics_SharedFixture_TestCase {

	public function test_getTagTypes_should_report_the_type_each_blocked_tag_is_stored_as() {
		$types = BlockedTemplates::getTagTypes();

		$this->assertContains( ( new HotjarTag() )->getId(), $types );
		$this->assertContains( ( new GoogleTagTag() )->getId(), $types );

		// this one names itself rather than letting the class name decide
		$this->assertContains( VisualWebsiteOptimizerTag::ID, $types );
		$this->assertContains( ( new CookiebotTag() )->getId(), $types );
	}

	public function test_getTagTypes_should_report_no_type_for_a_tag_that_is_not_blocked() {
		$types = BlockedTemplates::getTagTypes();

		$this->assertNotContains( ( new ThemeColorTag() )->getId(), $types );
		$this->assertNotContains( ( new MatomoTag() )->getId(), $types );

		// narrowed by SecuredTemplateFactory rather than blocked, so that the field that carries
		// the HTML is the one the user is told about
		$this->assertNotContains( ( new CustomHtmlTag() )->getId(), $types );
	}

	public function test_checkTagTypeIsAllowed_should_refuse_a_blocked_tag_to_a_user_without_unfiltered_html() {
		$this->become_a_user_without_unfiltered_html();

		$this->expectException( ValidatorException::class );
		$this->expectExceptionMessage( 'not allowed to choose which other services' );

		BlockedTemplates::checkTagTypeIsAllowed( ( new HotjarTag() )->getId() );
	}

	public function test_checkTagTypeIsAllowed_should_allow_a_tag_that_is_not_blocked() {
		$this->become_a_user_without_unfiltered_html();

		BlockedTemplates::checkTagTypeIsAllowed( ( new ThemeColorTag() )->getId() );

		$this->assertTrue( true, 'a tag that is not blocked has to be allowed through' );
	}

	public function test_checkTagTypeIsAllowed_should_allow_a_blocked_tag_to_a_user_with_unfiltered_html() {
		$user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );
		if ( ! current_user_can( 'unfiltered_html' ) ) {
			$this->markTestSkipped( 'this install does not grant administrators unfiltered_html.' );
		}

		BlockedTemplates::checkTagTypeIsAllowed( ( new HotjarTag() )->getId() );

		$this->assertTrue( true, 'a user who may write JavaScript decides what this site loads' );
	}

	public function test_checkTagTypeIsAllowed_should_allow_a_blocked_tag_while_the_constraints_are_suspended() {
		// what Tag Manager writes back out of its own tables is not somebody choosing a service
		$this->become_a_user_without_unfiltered_html();

		SecuredTemplateConstraints::suspendedFor(
			function () {
				BlockedTemplates::checkTagTypeIsAllowed( ( new HotjarTag() )->getId() );
			}
		);

		$this->assertTrue( true, 'a suspended write has to be allowed through' );
	}

	private function become_a_user_without_unfiltered_html() {
		$user_id = self::factory()->user->create( [ 'role' => Roles::ROLE_SUPERUSER ] );
		wp_set_current_user( $user_id );

		$this->assertFalse( current_user_can( 'unfiltered_html' ) );
	}
}
