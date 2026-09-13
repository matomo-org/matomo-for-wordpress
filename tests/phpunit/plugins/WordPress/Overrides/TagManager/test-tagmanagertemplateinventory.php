<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

use Piwik\Filesystem;
use Piwik\Plugin\Manager;
use Piwik\Plugins\WordPress\Overrides\TagManager\BlockedTemplates;
use Piwik\Plugins\WordPress\Overrides\TagManager\SecuredTemplateFactory;

/**
 * This test is used as a way to detect when Matomo core ships new Tag Manager templates.
 * When this occurs, the template must be manually examined to see if it is safe for users
 * without `unfiltered_html`. If it is not, a secured version must be registered.
 *
 * This test uses the tag-manager-templates.json file as the current known state of Tag
 * Manager templates. Each entry maps a template class to one of three verdicts:
 *
 * - `"safe"`: the template is left as Matomo ships it.
 * - `"narrowed"`: SecuredTemplateFactory registers a version of it with extra validations.
 * - `"blocked"`: BlockedTemplates refuses it to a user without the capability. Every one of
 *                these loads JavaScript from a third party the tag's own parameters name.
 *
 * Note: if a template is removed from core, this test will also fail. Removing the factory
 * method in this case would make the test pass.
 *
 * Note: this can only see what is on disk when it runs, and SecuredTemplateFactory matches
 * class names exactly, so a template shipped by a Matomo plugin installed later (eg, from the
 * Marketplace) is not caught by this test.
 *
 * @package matomo
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 */
class TagManagerTemplateInventoryTest extends MatomoAnalytics_SharedFixture_TestCase {

	const INVENTORY_FILE = __DIR__ . '/tag-manager-templates.json';

	const TEMPLATE_DIRECTORIES = [
		'Template/Tag'      => 'Piwik\Plugins\TagManager\Template\Tag\BaseTag',
		'Template/Trigger'  => 'Piwik\Plugins\TagManager\Template\Trigger\BaseTrigger',
		'Template/Variable' => 'Piwik\Plugins\TagManager\Template\Variable\BaseVariable',
	];

	public function test_tag_manager_should_ship_only_the_templates_this_plugin_has_reviewed() {
		$discovered = $this->discover_templates();
		$recorded   = array_keys( $this->read_inventory() );

		// compared as two differences to avoid failures due to order change

		$this->assertSame(
			[],
			array_values( array_diff( $discovered, $recorded ) ),
			'Tag Manager ships templates ' . basename( self::INVENTORY_FILE ) . ' has no verdict on.'
			. ' Read the new templates and determine whether they are safe or need a secured version.'
		);

		$this->assertSame(
			[],
			array_values( array_diff( $recorded, $discovered ) ),
			basename( self::INVENTORY_FILE ) . ' records templates Tag Manager no longer ships.'
			. ' Drop their entries, and any SecuredTemplateFactory method that stood in for them.'
		);
	}

	public function test_getTagReplacements_should_cover_exactly_the_tags_the_inventory_records_as_narrowed() {
		$this->assertSame(
			$this->templates_with_verdict( 'narrowed', 'Template\Tag' ),
			$this->replaced_templates( ( new SecuredTemplateFactory() )->getTagReplacements() )
		);
	}

	public function test_getVariableReplacements_should_cover_exactly_the_variables_the_inventory_records_as_narrowed() {
		$this->assertSame(
			$this->templates_with_verdict( 'narrowed', 'Template\Variable' ),
			$this->replaced_templates( ( new SecuredTemplateFactory() )->getVariableReplacements() )
		);
	}

	public function test_tag_manager_should_narrow_no_template_that_is_neither_a_tag_nor_a_variable() {
		// Only tags and variables have a replacement map, because those are the two the WordPress plugin
		// handles. Since the plugin does not replace triggers, there is no need to check whether a new
		// one has been added or not.

		$covered = array_merge(
			$this->templates_with_verdict( 'narrowed', 'Template\Tag' ),
			$this->templates_with_verdict( 'narrowed', 'Template\Variable' )
		);

		sort( $covered );

		$this->assertSame( $this->templates_with_verdict( 'narrowed' ), $covered );
	}

	public function test_BlockedTemplates_should_list_exactly_the_tags_the_inventory_records_as_blocked() {
		$listed = BlockedTemplates::TAGS;

		sort( $listed );

		$this->assertSame( $this->templates_with_verdict( 'blocked', 'Template\Tag' ), $listed );
	}

	public function test_tag_manager_should_block_no_template_that_is_not_a_tag() {
		// a blocked variable or trigger would have nowhere to be enforced, since BlockedTemplates is
		// only consulted where a tag is written

		$this->assertSame(
			$this->templates_with_verdict( 'blocked' ),
			$this->templates_with_verdict( 'blocked', 'Template\Tag' )
		);
	}

	public function test_BlockedTemplates_should_report_a_type_for_every_tag_it_lists() {
		$this->assertCount( count( BlockedTemplates::TAGS ), BlockedTemplates::getTagTypes() );
	}

	/**
	 * Discover Tag Manager template classes by manually looking through directories so we
	 * are not limited by what plugins happen to be activated at the time the test is run.
	 *
	 * @return string[] fully qualified class names, sorted
	 */
	private function discover_templates() {
		$templates = [];

		foreach ( Manager::getPluginsDirectories() as $plugins_dir ) {
			foreach ( scandir( $plugins_dir ) as $plugin_name ) {
				if ( '.' === $plugin_name[0] || ! is_dir( $plugins_dir . $plugin_name ) ) {
					continue;
				}

				foreach ( self::TEMPLATE_DIRECTORIES as $directory => $base_class ) {
					$base_dir = rtrim( $plugins_dir, '/' ) . '/' . $plugin_name . '/' . $directory;

					if ( ! is_dir( $base_dir ) ) {
						continue;
					}

					foreach ( Filesystem::globr( $base_dir, '*.php' ) as $file ) {
						require_once $file;

						$class_name = sprintf(
							'Piwik\Plugins\%s\%s\%s',
							$plugin_name,
							str_replace( '/', '\\', $directory ),
							str_replace( '/', '\\', str_replace( [ $base_dir . '/', '.php' ], '', $file ) )
						);

						if ( ! class_exists( $class_name ) || ! is_subclass_of( $class_name, $base_class ) ) {
							continue;
						}

						if ( ! ( new ReflectionClass( $class_name ) )->isInstantiable() ) {
							continue;
						}

						$templates[] = $class_name;
					}
				}
			}
		}

		sort( $templates );

		return $templates;
	}

	/**
	 * @return array<string, string> fully qualified class name => 'safe' or 'narrowed', sorted by key
	 */
	private function read_inventory() {
		$inventory = json_decode( file_get_contents( self::INVENTORY_FILE ), true );

		$this->assertNotNull( $inventory, self::INVENTORY_FILE . ' is not valid JSON' );

		$templates = $inventory['templates'];

		ksort( $templates );

		return $templates;
	}

	/**
	 * @param array<string, callable> $replacements the array value SecuredTemplateFactory returns
	 * @return string[] the class names, sorted
	 */
	private function replaced_templates( $replacements ) {
		$class_names = array_keys( $replacements );

		sort( $class_names );

		return $class_names;
	}

	/**
	 * @param string $verdict which verdict to collect, e.g. 'narrowed'
	 * @param string $kind    namespace fragment to keep, e.g. 'Template\Tag'. Everything when omitted.
	 * @return string[] the class names the inventory gives that verdict
	 */
	private function templates_with_verdict( $verdict, $kind = '' ) {
		$matched = [];

		foreach ( $this->read_inventory() as $class_name => $recorded ) {
			if ( $verdict !== $recorded ) {
				continue;
			}

			if ( '' === $kind || false !== strpos( $class_name, '\\' . $kind . '\\' ) ) {
				$matched[] = $class_name;
			}
		}

		return $matched;
	}
}
