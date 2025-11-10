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

	public function test_replaceUrls_replaces_plugin_urls_in_html() {
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

		$actual_output = $this->instance->replaceUrls( $html );
		$this->assertEquals( $expected_output, $actual_output );
	}

	public function test_replaceUrls_replaces_plugin_urls_in_json_in_html() {
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

		$actual_output = $this->instance->replaceUrls( $html );
		$this->assertEquals( $expected_output, $actual_output );
	}

	public function test_replaceUrls_replaces_urls_in_html_attributes() {
		$home_url   = home_url();
		$matomo_url = 'https://matomo.mysite.com';

		$html = <<<EOF
<html>
<head>
	<link src="index.php?module=Proxy&action=getCss" rel="stylesheet" />
	<script src="/index.php?module=Proxy&action=getCoreJs&cb=123"></script>
</head>
<body>
	<img src="$matomo_url/index.php?action=MyPlugin&module=getMyImage" />
	<img src="$matomo_url///?action=getMyData&module=MyOtherPlugin&idSite=1&period=day" />

	<img src="plugins/NonExistentPlugin/images/thing.png" />
	<img src="/some/other/folder/index.php?module=Whatever" />
	<img src="$matomo_url/some/other/folder/index.php?module=Whatever" />

	<script>
		var url = "/////index.php?";

		var fullUrl = "////index.php?module=Actions&action=getPageUrls&idSite=1";
</script>
</body>
</html>
EOF;

		// TODO: need setting to turn this on/off.
		$expected_output = <<<EOF
<html>
<head>
	<link src="$home_url/wp-admin/admin.php?module=Proxy&action=getCss&page=matomo-reporting" rel="stylesheet" />
	<script src="$home_url/wp-admin/admin.php?module=Proxy&action=getCoreJs&cb=123&page=matomo-reporting"></script>
</head>
<body>
	<img src="$home_url/wp-admin/admin.php?action=MyPlugin&module=getMyImage&page=matomo-reporting" />
	<img src="$home_url/wp-admin/admin.php?action=getMyData&module=MyOtherPlugin&idSite=1&period=day&page=matomo-reporting" />

	<img src="plugins/NonExistentPlugin/images/thing.png" />
	<img src="/some/other/folder/index.php?module=Whatever" />
	<img src="$matomo_url/some/other/folder/index.php?module=Whatever" />

	<script>
		var url = "/////index.php?";

		var fullUrl = "http://example.org/wp-admin/admin.php?module=Actions&action=getPageUrls&idSite=1&page=matomo-reporting";
</script>
</body>
</html>
EOF;

		$actual_output = $this->instance->replaceUrls( $matomo_url, $html );
		$this->assertEquals( $expected_output, $actual_output );
	}

	public function test_replaceUrls_replaces_urls_in_json_in_html() {
		$home_url   = home_url();
		$matomo_url = 'https://matomo.mysite.com';

		$json = [
			'images' => [
				'report'            => [
					'url1' => 'index.php?module=Actions&action=getSomething',
					'url2' => $matomo_url . '/index.php?action=getSomething&module=getWhatever&',
					'url3' => '/index.php?module=API&method=getWhatever',
					'url4' => '/some/other/folder/index.php',
				],
				'some other report' => [
					'url1' => '///index.php?module=Actions&action=',
					'url2' => $matomo_url . '///index.php?',
					'url3' => '/some/folder/index.php?module=Abc',
					'url4' => '/matomo.php?module=Abc',
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
				'report'            => [
					'url1' => $home_url . '/wp-admin/admin.php?module=Actions&action=getSomething&page=matomo-reporting',
					'url2' => $home_url . '/wp-admin/admin.php?action=getSomething&module=getWhatever&page=matomo-reporting',
					'url3' => $home_url . '/wp-admin/admin.php?module=API&method=getWhatever&page=matomo-reporting',
					'url4' => '/some/other/folder/index.php',
				],
				'some other report' => [
					'url1' => $home_url . '/wp-admin/admin.php?module=Actions&action=&page=matomo-reporting',
					'url2' => $matomo_url . '///index.php?',
					'url3' => '/some/folder/index.php?module=Abc',
					'url4' => '/matomo.php?module=Abc',
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

		$actual_output = $this->instance->replaceUrls( $matomo_url, $html );
		$this->assertEquals( $expected_output, $actual_output );
	}
}
