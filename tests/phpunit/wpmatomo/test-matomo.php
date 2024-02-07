<?php

/**
 * Test matomo.php.
 *
 * phpcs:disable WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
 *
 * @package matomo
 */
class MatomoTest extends MatomoUnit_TestCase {

	public function tear_down() {
		unset( $GLOBALS['MATOMO_MARKETPLACE_PLUGINS'] );

		if ( is_dir( dirname( $this->get_test_plugin_manifest_path() ) ) ) {
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_shell_exec
			shell_exec( 'rm -r ' . dirname( $this->get_test_plugin_manifest_path() ) );
		}

		parent::tear_down();
	}

	public function test_matomo_has_compatible_content_dir() {
		$this->assertTrue( matomo_has_compatible_content_dir() );
	}

	/**
	 * @dataProvider get_test_data_for_matomo_rel_path
	 */
	public function test_matomo_rel_path( $to_dir, $from_dir, $expected_rel_path ) {
		$actual = matomo_rel_path( $to_dir, $from_dir );
		$this->assertEquals( $expected_rel_path, $actual );
	}

	public function get_test_data_for_matomo_rel_path() {
		return [
			[ '/var/www/html/wordpress', '/var/www/html/wordpress/matomo/path', '../../' ],
			[ '/var/www/html/wordpress/matomo/path', '/var/www/html/wordpress', 'matomo/path' ],
			[ '/var/www/html/wordpress', '/var/www/html/wordpress', '' ],
			[ '/var/www/html/wordpress', '/var/www/matomo/for/wordpress', '../../../html/wordpress' ],
			[ '/var/www/matomo/for/wordpress', '/var/www/html/wordpress', '../../matomo/for/wordpress' ],
		];
	}

	/**
	 * @dataProvider get_test_data_for_matomo_is_plugin_compatible
	 */
	public function test_matomo_is_plugin_compatible( $plugin_json_contents, $expected ) {
		$this->mk_temp_dir();

		if ( null !== $plugin_json_contents ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
			file_put_contents( $this->get_test_plugin_manifest_path(), wp_json_encode( $plugin_json_contents ) );
		}

		$actual = matomo_is_plugin_compatible( __DIR__ . '/temp/PluginFile.php' );
		$this->assertEquals( $expected, $actual );
	}

	public function get_test_data_for_matomo_is_plugin_compatible() {
		$current_major_version = $this->get_current_major_version();

		$not_compatible_constraint = $this->get_not_compatible_constraint();
		$compatible_constraint     = $this->get_compatible_constraint();

		$incomplete_constraint_compatible   = '>=' . $current_major_version . '.0.0-b1';
		$incomplete_constraint_incompatible = '<' . ( $current_major_version - 1 ) . '.5.0';

		return [
			// no plugin.json
			[
				null,
				false,
			],

			// normal plugin.json, not compatible
			[
				[
					'require' => [ 'matomo' => $not_compatible_constraint ],
				],
				false,
			],

			// normal plugin.json, compatible
			[
				[
					'require' => [ 'matomo' => $compatible_constraint ],
				],
				true,
			],

			// plugin.json w/ non-core requirements, not compatible
			[
				[
					'require' => [
						'matomo'        => $compatible_constraint,
						'AnotherPlugin' => 'ignored',
					],
				],
				true,
			],
			[
				[
					'require' => [
						'piwik'         => $compatible_constraint,
						'AnotherPlugin' => 'ignored',
					],
				],
				true,
			],

			// plugin.json w/ non-core requirements, compatible
			[
				[
					'require' => [
						'matomo'        => $not_compatible_constraint,
						'AnotherPlugin' => 'ignored',
					],
				],
				false,
			],
			[
				[
					'require' => [
						'piwik'         => $not_compatible_constraint,
						'AnotherPlugin' => 'ignored',
					],
				],
				false,
			],

			// plugin.json w/ missing values
			[
				[
					'require' => [],
				],
				false,
			],
			[
				[],
				false,
			],

			// plugin.json w/ incomplete constraints
			[
				[
					'require' => [ 'matomo' => $incomplete_constraint_compatible ],
				],
				true,
			],
			[
				[
					'require' => [ 'matomo' => $incomplete_constraint_incompatible ],
				],
				false,
			],
		];
	}

	public function test_matomo_is_plugin_compatible_rechecks_if_plugin_manifest_changes() {
		$this->mk_temp_dir();

		$not_compatible_constraint = $this->get_not_compatible_constraint();
		$compatible_constraint     = $this->get_compatible_constraint();

		$plugin_json_contents = [
			'require' => [ 'matomo' => $not_compatible_constraint ],
		];

		file_put_contents( $this->get_test_plugin_manifest_path(), wp_json_encode( $plugin_json_contents ) );

		$actual = matomo_is_plugin_compatible( __DIR__ . '/temp/PluginFile.php' );
		$this->assertFalse( $actual );

		sleep( 1 ); // so file modified time increases

		$plugin_json_contents = [
			'require' => [ 'matomo' => $compatible_constraint ],
		];

		file_put_contents( $this->get_test_plugin_manifest_path(), wp_json_encode( $plugin_json_contents ) );

		$actual = matomo_is_plugin_compatible( __DIR__ . '/temp/PluginFile.php' );
		$this->assertTrue( $actual );
	}

	public function test_matomo_filter_incompatible_plugins() {
		$this->mk_temp_dir();

		$GLOBALS['MATOMO_MARKETPLACE_PLUGINS'] = [
			__DIR__ . '/temp/CompatiblePlugin/CompatiblePlugin.php',
			__DIR__ . '/temp/IncompatiblePlugin/IncompatiblePlugin.php',
		];

		$not_compatible_constraint = $this->get_not_compatible_constraint();
		$compatible_constraint     = $this->get_compatible_constraint();

		mkdir( __DIR__ . '/temp/CompatiblePlugin' );
		file_put_contents(
			__DIR__ . '/temp/CompatiblePlugin/plugin.json',
			wp_json_encode(
				[
					'require' => [ 'matomo' => $compatible_constraint ],
				]
			)
		);

		mkdir( __DIR__ . '/temp/IncompatiblePlugin' );
		file_put_contents(
			__DIR__ . '/temp/IncompatiblePlugin/plugin.json',
			wp_json_encode(
				[
					'require' => [ 'matomo' => $not_compatible_constraint ],
				]
			)
		);

		$actual = [ 'CompatiblePlugin', 'IncompatiblePlugin', 'CorePlugin' ];
		matomo_filter_incompatible_plugins( $actual );

		$expected = [ 'CompatiblePlugin', 'CorePlugin' ];
		$this->assertEquals( $expected, $actual );
	}

	private function get_test_plugin_manifest_path() {
		return __DIR__ . '/temp/plugin.json';
	}

	private function get_current_major_version() {
		require_once __DIR__ . '/../../../app/core/Version.php';
		return \Piwik\Version::MAJOR_VERSION;
	}

	private function get_not_compatible_constraint() {
		$current_major_version = $this->get_current_major_version();
		return '>=' . ( $current_major_version - 1 ) . '.0.0-b1,<' . $current_major_version . '.0.0-b1';
	}

	private function get_compatible_constraint() {
		$current_major_version = $this->get_current_major_version();
		return '>=' . $current_major_version . '.0.0-b1,<' . ( $current_major_version + 1 ) . '.0.0-b1';
	}

	private function mk_temp_dir() {
		clearstatcache();

		$dir = dirname( $this->get_test_plugin_manifest_path() );
		if ( ! is_dir( $dir ) ) {
			mkdir( $dir, 0777, true );
		}
	}
}
