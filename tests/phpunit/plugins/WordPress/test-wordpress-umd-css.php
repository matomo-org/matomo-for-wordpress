<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

use Piwik\AssetManager\UIAssetFetcher\StylesheetUIAssetFetcher;
use Piwik\Plugin\Manager;
use Piwik\Theme;

/**
 * phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet
 * phpcs:disable WordPress.WP.AlternativeFunctions
 * phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged
 */
class WordPressUmdCssTest extends MatomoAnalytics_SharedFixture_TestCase {

	const CUSTOM_CSS_PLUGIN    = 'WpUmdCssTestPlugin';
	const CUSTOM_NO_CSS_PLUGIN = 'WpUmdNoCssTestPlugin';
	const CORE_PLUGIN          = 'CoreHome';

	/**
	 * @var \Piwik\Plugins\WordPress\WordPress
	 */
	private $plugin;

	/**
	 * @var string absolute path to the temporary custom plugins directory
	 */
	private $custom_plugins_dir;

	/**
	 * @var array|null
	 */
	private $original_plugin_dirs_global;

	public function setUp(): void {
		parent::setUp();

		\WpMatomo\Bootstrap::do_bootstrap();

		// getPluginDirectory() caches its lookups statically; reset so our freshly registered
		// custom directory is picked up (and does not leak into other tests)
		$this->reset_plugin_path_caches();

		$this->custom_plugins_dir          = rtrim( sys_get_temp_dir(), '/' ) . '/matomo-umd-css-test-plugins';
		$this->original_plugin_dirs_global = isset( $GLOBALS['MATOMO_PLUGIN_DIRS'] ) ? $GLOBALS['MATOMO_PLUGIN_DIRS'] : null;

		// a custom directory plugin that ships a compiled Vue UMD bundle stylesheet
		wp_mkdir_p( $this->custom_plugins_dir . '/' . self::CUSTOM_CSS_PLUGIN . '/vue/dist' );
		file_put_contents(
			$this->custom_plugins_dir . '/' . self::CUSTOM_CSS_PLUGIN . '/vue/dist/' . self::CUSTOM_CSS_PLUGIN . '.css',
			'.foo{}'
		);

		// a custom directory plugin without a bundle stylesheet
		wp_mkdir_p( $this->custom_plugins_dir . '/' . self::CUSTOM_NO_CSS_PLUGIN );

		$GLOBALS['MATOMO_PLUGIN_DIRS']   = isset( $GLOBALS['MATOMO_PLUGIN_DIRS'] ) ? $GLOBALS['MATOMO_PLUGIN_DIRS'] : [];
		$GLOBALS['MATOMO_PLUGIN_DIRS'][] = [
			'pluginsPathAbsolute'        => $this->custom_plugins_dir,
			'webrootDirRelativeToMatomo' => 'wp-content/uploads/matomo-umd-css-test-plugins',
		];

		$this->plugin = Manager::getInstance()->getLoadedPlugin( 'WordPress' );
	}

	public function tearDown(): void {
		if ( null === $this->original_plugin_dirs_global ) {
			unset( $GLOBALS['MATOMO_PLUGIN_DIRS'] );
		} else {
			$GLOBALS['MATOMO_PLUGIN_DIRS'] = $this->original_plugin_dirs_global;
		}

		$this->reset_plugin_path_caches();
		\Piwik\Filesystem::unlinkRecursive( $this->custom_plugins_dir, true );

		parent::tearDown();
	}

	public function test_getCustomPluginDirUmdCssFile_returns_location_for_custom_dir_plugin_with_bundle_css() {
		$expected = 'plugins/' . self::CUSTOM_CSS_PLUGIN . '/vue/dist/' . self::CUSTOM_CSS_PLUGIN . '.css';

		$this->assertSame( $expected, $this->plugin->getCustomPluginDirUmdCssFileIfExists( self::CUSTOM_CSS_PLUGIN ) );
	}

	public function test_getCustomPluginDirUmdCssFile_returns_null_for_custom_dir_plugin_without_bundle_css() {
		$this->assertNull( $this->plugin->getCustomPluginDirUmdCssFileIfExists( self::CUSTOM_NO_CSS_PLUGIN ) );
	}

	public function test_getCustomPluginDirUmdCssFile_returns_null_for_core_plugin() {
		// core plugins are already handled by Matomo core, so nothing extra must be added for them
		$this->assertNull( $this->plugin->getCustomPluginDirUmdCssFileIfExists( self::CORE_PLUGIN ) );
	}

	public function test_getCustomPluginDirUmdCssFile_returns_null_for_unknown_plugin() {
		$this->assertNull( $this->plugin->getCustomPluginDirUmdCssFileIfExists( 'ThisPluginDoesNotExistAnywhere' ) );
	}

	public function test_getStylesheetFiles_still_adds_the_static_wordpress_stylesheets() {
		$files = [];
		$this->plugin->getStylesheetFiles( $files );

		$this->assertContains( '../plugins/WordPress/stylesheets/user.css', $files );
		$this->assertContains( '../plugins/WordPress/stylesheets/overrides.css', $files );
	}

	/**
	 * the returned 'plugins/<Plugin>/...' string is a lookup location, not a literal path under
	 * PIWIK_INCLUDE_PATH . '/plugins'. this test is meant for Matomo's real asset resolution, to
	 * prove the location is remapped to the custom plugins directory.
	 */
	public function test_returned_location_resolves_to_the_custom_plugins_directory() {
		$location = $this->plugin->getCustomPluginDirUmdCssFileIfExists( self::CUSTOM_CSS_PLUGIN );
		$this->assertNotNull( $location );

		$asset = $this->resolve_asset_for_location( $location );

		$this->assertNotNull( $asset, 'the stylesheet location was not resolved to an asset' );

		// the file that gets read/merged must be the one that physically exists in the custom dir
		$this->assertTrue( $asset->exists() );
		$expected_file = $this->custom_plugins_dir . '/' . self::CUSTOM_CSS_PLUGIN . '/vue/dist/' . self::CUSTOM_CSS_PLUGIN . '.css';
		$this->assertSame( realpath( $expected_file ), realpath( $asset->getAbsoluteLocation() ) );

		// the generated web location must use the plugin's registered web root, not 'plugins/...'
		$this->assertStringStartsWith( 'wp-content/uploads/matomo-umd-css-test-plugins/', $asset->getRelativeLocation() );
		$this->assertStringEndsWith(
			self::CUSTOM_CSS_PLUGIN . '/vue/dist/' . self::CUSTOM_CSS_PLUGIN . '.css',
			$asset->getRelativeLocation()
		);
	}

	private function resolve_asset_for_location( $location ) {
		$theme   = new Theme();
		$fetcher = new StylesheetUIAssetFetcher( [], $theme );

		// need to use reflection to use private members for this test
		$declaring_class = \Piwik\AssetManager\UIAssetFetcher::class;

		$file_locations = new ReflectionProperty( $declaring_class, 'fileLocations' );
		$file_locations->setAccessible( true );
		$file_locations->setValue( $fetcher, [ $location ] );

		foreach ( [ 'initCatalog', 'populateCatalog' ] as $method_name ) {
			$method = new ReflectionMethod( $declaring_class, $method_name );
			$method->setAccessible( true );
			$method->invoke( $fetcher );
		}

		$catalog = new ReflectionProperty( $declaring_class, 'catalog' );
		$catalog->setAccessible( true );
		$resolved_catalog = $catalog->getValue( $fetcher );

		foreach ( $resolved_catalog->getAssets() as $asset ) {
			if ( false !== strpos( $asset->getRelativeLocation(), self::CUSTOM_CSS_PLUGIN ) ) {
				return $asset;
			}
		}

		return null;
	}

	private function reset_plugin_path_caches() {
		$reflection = new ReflectionClass( Manager::class );

		foreach ( [ 'pluginsToPathCache', 'pluginsToWebRootDirCache' ] as $property_name ) {
			$property = $reflection->getProperty( $property_name );
			$property->setAccessible( true );
			$property->setValue( null, [] );
		}
	}
}
