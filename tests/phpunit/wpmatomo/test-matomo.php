<?php
/**
 * Test matomo.php.
 *
 * @package matomo
 */
class MatomoTest extends MatomoUnit_TestCase {

	public function tear_down() {
		if ( is_file( $this->get_test_plugin_manifest_path() ) ) {
			unlink( $this->get_test_plugin_manifest_path() );
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
		if ( null !== $plugin_json_contents ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
			file_put_contents( $this->get_test_plugin_manifest_path(), wp_json_encode( $plugin_json_contents ) );
		}

		$actual = matomo_is_plugin_compatible( __DIR__ . '/PluginFile.php' );
		$this->assertEquals( $expected, $actual );
	}

	public function get_test_data_for_matomo_is_plugin_compatible() {
		require_once __DIR__ . '/../../../app/core/Version.php';

		$current_major_version = \Piwik\Version::MAJOR_VERSION;

		// >=5.0.0-b4,<6.0.0-b1
		$not_compatible_constraint = '>=' . ( $current_major_version - 1 ) . '.0.0-b1,<' . $current_major_version . '.0.0-b1';
		$compatible_constraint     = '>=' . $current_major_version . '.0.0-b1,<' . ( $current_major_version + 1 ) . '.0.0-b1';

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

	private function get_test_plugin_manifest_path() {
		return __DIR__ . '/plugin.json';
	}
}
