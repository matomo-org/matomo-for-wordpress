<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

use Piwik\Application\Kernel\GlobalSettingsProvider as DefaultGlobalSettingsProvider;
use Piwik\Plugins\WordPress\Overrides\GlobalSettingsProvider;

/**
 * @package matomo
 */
class GlobalSettingsProviderTest extends MatomoAnalytics_TestCase {

	/**
	 * @var \WpMatomo\Settings
	 */
	private $settings;

	public function setUp(): void {
		parent::setUp();

		delete_option( \WpMatomo\Settings::OPTION_GLOBAL );
		$this->settings = new \WpMatomo\Settings();
	}

	public function test_is_a_global_settings_provider() {
		$provider = new GlobalSettingsProvider( null, null, null, $this->settings );
		$this->assertInstanceOf( DefaultGlobalSettingsProvider::class, $provider );
	}

	public function test_construct_seeds_option_with_overrides_only_when_option_has_no_data() {
		$this->assertFalse( $this->get_option_data() );

		$provider = new GlobalSettingsProvider( null, null, null, $this->settings );

		$stored = $this->get_option_data();

		$this->assertIsArray( $stored );
		$this->assertNotEmpty( $stored );

		// only the diff (values that differ from the INI defaults) is persisted, not the full merged
		// config. so the stored data must be a strict subset of the merged settings and must not
		// contain unchanged default-only sections.
		$merged = $provider->getIniFileChain()->getAll();
		$this->assertNotEquals( $merged, $stored );
		foreach ( $stored as $section_name => $section ) {
			$this->assertArrayHasKey( $section_name, $merged );
		}
	}

	public function test_construct_seeds_option_with_overrides_only_when_option_holds_an_empty_array() {
		$this->update_option_data( [] );

		$provider = new GlobalSettingsProvider( null, null, null, $this->settings );

		$stored = $this->get_option_data();

		$this->assertNotEmpty( $stored );
		$this->assertNotEquals( $provider->getIniFileChain()->getAll(), $stored );
	}

	public function test_construct_does_not_overwrite_existing_option_data() {
		$existing = array(
			'General' => array( 'foo' => 'bar' ),
		);
		$this->update_option_data( $existing );

		new GlobalSettingsProvider( null, null, null, $this->settings );

		// the existing option data must not be replaced by the INI data when constructing.
		$this->assertEquals( $existing, $this->get_option_data() );
	}

	public function test_construct_loads_config_overrides_from_option_when_option_has_data() {
		$stored_config = array(
			'TestSection' => array( 'test_key' => 'test_value' ),
		);
		$this->update_option_data( $stored_config );

		$provider = new GlobalSettingsProvider( null, null, null, $this->settings );

		// the stored override is read from the option and applied on top of the INI config
		$this->assertSame( 'test_value', $provider->getSection( 'TestSection' )['test_key'] );
	}

	public function test_construct_does_not_wipe_default_sections_when_option_holds_partial_data() {
		// this guards against the regression where partial stored data (eg. data written by older
		// plugin versions) replaced the whole config and wiped the INI defaults.
		$default_general = ( new GlobalSettingsProvider( null, null, null, $this->settings ) )->getSection( 'General' );
		$this->assertNotEmpty( $default_general, 'precondition: the General section has default values' );

		delete_option( \WpMatomo\Settings::OPTION_GLOBAL );
		$this->settings = new \WpMatomo\Settings();
		$this->update_option_data(
			array(
				'TestSection' => array( 'test_key' => 'test_value' ),
			)
		);

		$provider = new GlobalSettingsProvider( null, null, null, $this->settings );

		// the partial override is applied...
		$this->assertSame( 'test_value', $provider->getSection( 'TestSection' )['test_key'] );
		// ...but the default sections that are not part of the stored data are preserved.
		$this->assertEquals( $default_general, $provider->getSection( 'General' ) );
	}

	public function test_construct_reapplies_plugin_list_modifier_after_loading_stored_config() {
		// a stale/frozen plugin list stored in the option (eg. seeded on an earlier request)
		$this->update_option_data(
			array(
				'Plugins' => array( 'Plugins' => array( 'CoreHome', 'StalePlugin' ) ),
			)
		);

		$original_modifier                        = isset( $GLOBALS['MATOMO_MODIFY_CONFIG_SETTINGS'] ) ? $GLOBALS['MATOMO_MODIFY_CONFIG_SETTINGS'] : null;
		$GLOBALS['MATOMO_MODIFY_CONFIG_SETTINGS'] = function ( $settings ) {
			// simulate the dynamic plugin list computation that runs on every request
			$settings['Plugins']['Plugins'][] = 'DynamicallyAddedPlugin';
			return $settings;
		};

		try {
			$provider = new GlobalSettingsProvider( null, null, null, $this->settings );
			$plugins  = $provider->getSection( 'Plugins' )['Plugins'];
		} finally {
			if ( null === $original_modifier ) {
				unset( $GLOBALS['MATOMO_MODIFY_CONFIG_SETTINGS'] );
			} else {
				$GLOBALS['MATOMO_MODIFY_CONFIG_SETTINGS'] = $original_modifier;
			}
		}

		// the stored plugin activation state is applied...
		$this->assertContains( 'StalePlugin', $plugins );
		// ...but the modifier is re-applied on top, so the dynamically computed plugin list (used by
		// PluginList and the DI container) is not frozen to the stored value.
		$this->assertContains( 'DynamicallyAddedPlugin', $plugins );
	}

	public function test_persistConfigOption_reflects_config_changes_in_the_option() {
		$provider = new GlobalSettingsProvider( null, null, null, $this->settings );

		$chain   = $provider->getIniFileChain();
		$section = $chain->get( 'TestSection' );
		$this->assertArrayNotHasKey( 'changed_key', (array) $section );

		// change the config data the same way Piwik\Config does (through IniFileChain)
		$section                = (array) $section;
		$section['changed_key'] = 'changed_value';
		$chain->set( 'TestSection', $section );

		$provider->persistConfigOption();

		$stored = $this->get_option_data();

		$this->assertSame( 'changed_value', $stored['TestSection']['changed_key'] );
		// only the override is stored, not the full merged config
		$this->assertNotEquals( $chain->getAll(), $stored );
	}

	private function get_option_data() {
		return $this->settings->get_global_option( \WpMatomo\Settings::NETWORK_CONFIG_OPTIONS );
	}

	private function update_option_data( array $data ) {
		$this->settings->set_global_option( \WpMatomo\Settings::NETWORK_CONFIG_OPTIONS, $data );
		$this->settings->save();
	}
}
