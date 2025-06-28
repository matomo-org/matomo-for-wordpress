<?php

use Piwik\Plugins\WordPress\Html\PluginUrlReplacer;

/**
 * @package matomo
 * phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet
 * phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedScript
 */
class PluginUrlReplacerTest extends MatomoUnit_TestCase {

	private $instance;

	private $original_plugin_files_global;

	public function setUp(): void {
		parent::setUp();

		// autoloading for this file would only work if we bootstrap Matomo, but that
		// would be overkill for this test
		require_once __DIR__ . '/../../../../../app/core/Common.php';
		require_once __DIR__ . '/../../../../../plugins/WordPress/Html/PluginUrlReplacer.php';

		$this->original_plugin_files_global = $GLOBALS['MATOMO_PLUGIN_FILES'];
		$GLOBALS['MATOMO_PLUGIN_FILES'][]   = ABSPATH . '/wp-content/plugins/MyPlugin/MyPlugin.php';
		$GLOBALS['MATOMO_PLUGIN_FILES'][]   = ABSPATH . '/wp-content/plugins/MyOtherPlugin/MyOtherPlugin.php';

		$this->instance = new PluginUrlReplacer();
	}

	public function tearDown(): void {
		$GLOBALS['MATOMO_PLUGIN_FILES'] = $this->original_plugin_files_global;
		parent::tearDown();
	}

	public function test_replaceThirdPartyPluginUrls_replaces_plugin_urls_in_html() {
		$html = <<<EOF
<html>
<head>
	<link src="plugins/MyPlugin/stylesheets/file.css" rel="stylesheet" />
	<script src="plugins/MyPlugin/javascripts/myscript.js"></script>
</head>
<body>
	<img src="plugins/MyOtherPlugin/images/myimage.png" />
	<img src="./plugins/MyOtherPlugin/images/myotherimage.png" />

	<img src="plugins/NonExistentPlugin/images/thing.png" />
</body>
</html>
EOF;

		// phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet
		$expected_output = <<<EOF
<html>
<head>
	<link src="http://example.org/wp-content/plugins/MyPlugin/stylesheets/file.css" rel="stylesheet" />
	<script src="http://example.org/wp-content/plugins/MyPlugin/javascripts/myscript.js"></script>
</head>
<body>
	<img src="http://example.org/wp-content/plugins/MyOtherPlugin/images/myimage.png" />
	<img src="http://example.org/wp-content/plugins/MyOtherPlugin/images/myotherimage.png" />

	<img src="plugins/NonExistentPlugin/images/thing.png" />
</body>
</html>
EOF;

		$actual_output = $this->instance->replaceThirdPartyPluginUrls( $html );
		$this->assertEquals( $expected_output, $actual_output );
	}

	public function test_replaceThirdPartyPluginUrls_replaces_plugin_urls_in_json_in_html() {
		$json = [
			'images' => [
				'someproduct'        => [
					'logo' => 'plugins/MyPlugin/images/someproduct.png',
				],
				'some other product' => [
					'logo' => 'plugins/MyOtherPlugin/images/someotherproduct.png',
				],
			],
		];
		$json = wp_json_encode( $json );
		$json = esc_attr( $json );

		$html = <<<EOF
<html>
<body>

<div
	some-param="$json"
></div>
</body>
</html>
EOF;

		$altered_json = [
			'images' => [
				'someproduct'        => [
					'logo' => 'http://example.org/wp-content/plugins/MyPlugin/images/someproduct.png',
				],
				'some other product' => [
					'logo' => 'http://example.org/wp-content/plugins/MyOtherPlugin/images/someotherproduct.png',
				],
			],
		];
		$altered_json = wp_json_encode( $altered_json );
		$altered_json = esc_attr( $altered_json );

		$expected_output = <<<EOF
<html>
<body>

<div
	some-param="$altered_json"
></div>
</body>
</html>
EOF;

		$actual_output = $this->instance->replaceThirdPartyPluginUrls( $html );
		$this->assertEquals( $expected_output, $actual_output );
	}
}
