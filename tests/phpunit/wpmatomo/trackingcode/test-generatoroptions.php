<?php
/**
 * @package matomo
 */

use WpMatomo\TrackingCode\GeneratorOptions;

class GeneratorOptionsTest extends MatomoUnit_TestCase {
	public function test_construct_with_empty_settings() {
		$settings = new \WpMatomo\Settings();

		$options = new GeneratorOptions( $settings );

		$this->assertEquals( $settings->default_global_settings['track_api_endpoint'], $options->get_track_api_endpoint() );
		$this->assertEquals( $settings->default_global_settings['force_protocol'], $options->get_force_protocol() );
		$this->assertEquals( $settings->default_global_settings['track_js_endpoint'], $options->get_track_js_endpoint() );
		$this->assertEquals( $settings->default_global_settings['set_download_extensions'], $options->get_set_download_extensions() );
		$this->assertEquals( $settings->default_global_settings['add_download_extensions'], $options->get_add_download_extensions() );
		$this->assertEquals( $settings->default_global_settings['set_download_classes'], $options->get_set_download_classes() );
		$this->assertEquals( $settings->default_global_settings['set_link_classes'], $options->get_set_link_classes() );
		$this->assertEquals( $settings->default_global_settings['disable_cookies'], $options->get_disable_cookies() );
		$this->assertEquals( $settings->default_global_settings['track_crossdomain_linking'], $options->get_track_crossdomain_linking() );
		$this->assertEquals( $settings->default_global_settings['track_jserrors'], $options->get_track_jserrors() );
		$this->assertEquals( $settings->default_global_settings['track_across'], $options->get_track_across() );
		$this->assertEquals( $settings->default_global_settings['track_across_alias'], $options->get_track_across_alias() );
		$this->assertEquals( $settings->default_global_settings['force_post'], $options->get_force_post() );
		$this->assertEquals( $settings->default_global_settings['cookie_consent'], $options->get_cookie_consent() );
		$this->assertEquals( $settings->default_global_settings['limit_cookies'], $options->get_limit_cookies() );
		$this->assertEquals( $settings->default_global_settings['limit_cookies_visitor'], $options->get_limit_cookies_visitor() );
		$this->assertEquals( $settings->default_global_settings['limit_cookies_referral'], $options->get_limit_cookies_referral() );
		$this->assertEquals( $settings->default_global_settings['limit_cookies_session'], $options->get_limit_cookies_session() );
		$this->assertEquals( $settings->default_global_settings['track_datacfasync'], $options->get_track_datacfasync() );
		$this->assertEquals( $settings->default_global_settings['track_heartbeat'], $options->get_track_heartbeat() );
		$this->assertEquals( $settings->default_global_settings['track_content'], $options->get_track_content() );
	}

	public function test_construct_with_settings_missing_values() {
		$settings = new \WpMatomo\Settings();
		$settings->set_global_option( 'track_api_endpoint', 'test value 1' );
		$settings->set_global_option( 'force_protocol', 'test value 2' );
		$settings->set_global_option( 'track_js_endpoint', 'test value 3' );
		$settings->set_global_option( 'set_download_extensions', 'test value 4' );
		$settings->set_global_option( 'add_download_extensions', 'test value 5' );
		$settings->set_global_option( 'set_download_classes', 'test value 6' );
		$settings->set_global_option( 'set_link_classes', 'test value 7' );
		$settings->set_global_option( 'track_crossdomain_linking', 'test value 9' );
		$settings->set_global_option( 'track_jserrors', 'test value 10' );
		$settings->set_global_option( 'track_across', 'test value 11' );
		$settings->set_global_option( 'force_post', 'test value 13' );
		$settings->set_global_option( 'cookie_consent', 'test value 14' );
		$settings->set_global_option( 'limit_cookies', 'test value 15' );
		$settings->set_global_option( 'limit_cookies_visitor', 16 );
		$settings->set_global_option( 'limit_cookies_referral', 17 );
		$settings->set_global_option( 'limit_cookies_session', 18 );
		$settings->set_global_option( 'track_heartbeat', 20 );
		$settings->set_global_option( 'track_content', 'test value 21' );

		$options = new GeneratorOptions( $settings );

		$this->assertEquals( 'test value 1', $options->get_track_api_endpoint() );
		$this->assertEquals( 'test value 2', $options->get_force_protocol() );
		$this->assertEquals( 'test value 3', $options->get_track_js_endpoint() );
		$this->assertEquals( 'test value 4', $options->get_set_download_extensions() );
		$this->assertEquals( 'test value 5', $options->get_add_download_extensions() );
		$this->assertEquals( 'test value 6', $options->get_set_download_classes() );
		$this->assertEquals( 'test value 7', $options->get_set_link_classes() );
		$this->assertEquals( null, $options->get_disable_cookies() );
		$this->assertEquals( 'test value 9', $options->get_track_crossdomain_linking() );
		$this->assertEquals( 'test value 10', $options->get_track_jserrors() );
		$this->assertEquals( 'test value 11', $options->get_track_across() );
		$this->assertEquals( null, $options->get_track_across_alias() );
		$this->assertEquals( 'test value 13', $options->get_force_post() );
		$this->assertEquals( 'test value 14', $options->get_cookie_consent() );
		$this->assertEquals( 'test value 15', $options->get_limit_cookies() );
		$this->assertEquals( 16, $options->get_limit_cookies_visitor() );
		$this->assertEquals( 17, $options->get_limit_cookies_referral() );
		$this->assertEquals( 18, $options->get_limit_cookies_session() );
		$this->assertEquals( null, $options->get_track_datacfasync() );
		$this->assertEquals( 20, $options->get_track_heartbeat() );
		$this->assertEquals( 'test value 21', $options->get_track_content() );
	}

	public function test_construct_with_settings() {
		$settings = new \WpMatomo\Settings();
		$settings->set_global_option( 'track_api_endpoint', 'test value 1' );
		$settings->set_global_option( 'force_protocol', 'test value 2' );
		$settings->set_global_option( 'track_js_endpoint', 'test value 3' );
		$settings->set_global_option( 'set_download_extensions', 'test value 4' );
		$settings->set_global_option( 'add_download_extensions', 'test value 5' );
		$settings->set_global_option( 'set_download_classes', 'test value 6' );
		$settings->set_global_option( 'set_link_classes', 'test value 7' );
		$settings->set_global_option( 'disable_cookies', 'test value 8' );
		$settings->set_global_option( 'track_crossdomain_linking', 'test value 9' );
		$settings->set_global_option( 'track_jserrors', 'test value 10' );
		$settings->set_global_option( 'track_across', 'test value 11' );
		$settings->set_global_option( 'track_across_alias', 'test value 12' );
		$settings->set_global_option( 'force_post', 'test value 13' );
		$settings->set_global_option( 'cookie_consent', 'test value 14' );
		$settings->set_global_option( 'limit_cookies', 'test value 15' );
		$settings->set_global_option( 'limit_cookies_visitor', 16 );
		$settings->set_global_option( 'limit_cookies_referral', 17 );
		$settings->set_global_option( 'limit_cookies_session', 18 );
		$settings->set_global_option( 'track_datacfasync', 'test value 19' );
		$settings->set_global_option( 'track_heartbeat', 20 );
		$settings->set_global_option( 'track_content', 'test value 21' );

		$options = new GeneratorOptions( $settings );

		$this->assertEquals( 'test value 1', $options->get_track_api_endpoint() );
		$this->assertEquals( 'test value 2', $options->get_force_protocol() );
		$this->assertEquals( 'test value 3', $options->get_track_js_endpoint() );
		$this->assertEquals( 'test value 4', $options->get_set_download_extensions() );
		$this->assertEquals( 'test value 5', $options->get_add_download_extensions() );
		$this->assertEquals( 'test value 6', $options->get_set_download_classes() );
		$this->assertEquals( 'test value 7', $options->get_set_link_classes() );
		$this->assertEquals( 'test value 8', $options->get_disable_cookies() );
		$this->assertEquals( 'test value 9', $options->get_track_crossdomain_linking() );
		$this->assertEquals( 'test value 10', $options->get_track_jserrors() );
		$this->assertEquals( 'test value 11', $options->get_track_across() );
		$this->assertEquals( 'test value 12', $options->get_track_across_alias() );
		$this->assertEquals( 'test value 13', $options->get_force_post() );
		$this->assertEquals( 'test value 14', $options->get_cookie_consent() );
		$this->assertEquals( 'test value 15', $options->get_limit_cookies() );
		$this->assertEquals( 16, $options->get_limit_cookies_visitor() );
		$this->assertEquals( 17, $options->get_limit_cookies_referral() );
		$this->assertEquals( 18, $options->get_limit_cookies_session() );
		$this->assertEquals( 'test value 19', $options->get_track_datacfasync() );
		$this->assertEquals( 20, $options->get_track_heartbeat() );
		$this->assertEquals( 'test value 21', $options->get_track_content() );
	}

	public function test_construct_with_request_missing_values() {
		$settings = new \WpMatomo\Settings();
		$request  = [
			'track_api_endpoint'        => 'test value 1',
			'force_protocol'            => 'test value 2',
			'track_js_endpoint'         => 'test value 3',
			'set_download_extensions'   => 'test value 4',
			'add_download_extensions'   => 'test value 5',
			'set_download_classes'      => 'test value 6',
			'set_link_classes'          => 'test value 7',
			'track_crossdomain_linking' => 'test value 9',
			'track_jserrors'            => 'test value 10',
			'track_across'              => 'test value 11',
			'track_across_alias'        => 'test value 12',
			'force_post'                => 'test value 13',
			'cookie_consent'            => 'test value 14',
			'limit_cookies'             => 'test value 15',
			'limit_cookies_referral'    => 'test value 17',
			'limit_cookies_session'     => 'test value 18',
			'track_heartbeat'           => 'test value 19',
			'track_content'             => 'test value 21',
		];

		$options = new GeneratorOptions( $settings, $request );

		$this->assertEquals( 'test value 1', $options->get_track_api_endpoint() );
		$this->assertEquals( 'test value 2', $options->get_force_protocol() );
		$this->assertEquals( 'test value 3', $options->get_track_js_endpoint() );
		$this->assertEquals( 'test value 4', $options->get_set_download_extensions() );
		$this->assertEquals( 'test value 5', $options->get_add_download_extensions() );
		$this->assertEquals( 'test value 6', $options->get_set_download_classes() );
		$this->assertEquals( 'test value 7', $options->get_set_link_classes() );
		$this->assertEquals( null, $options->get_disable_cookies() );
		$this->assertEquals( 'test value 9', $options->get_track_crossdomain_linking() );
		$this->assertEquals( 'test value 10', $options->get_track_jserrors() );
		$this->assertEquals( 'test value 11', $options->get_track_across() );
		$this->assertEquals( 'test value 12', $options->get_track_across_alias() );
		$this->assertEquals( 'test value 13', $options->get_force_post() );
		$this->assertEquals( 'test value 14', $options->get_cookie_consent() );
		$this->assertEquals( 'test value 15', $options->get_limit_cookies() );
		$this->assertEquals( 34186669, $options->get_limit_cookies_visitor() );
		$this->assertEquals( 'test value 17', $options->get_limit_cookies_referral() );
		$this->assertEquals( 'test value 18', $options->get_limit_cookies_session() );
		$this->assertEquals( 'test value 19', $options->get_track_heartbeat() );
		$this->assertEquals( null, $options->get_track_datacfasync() );
		$this->assertEquals( 'test value 21', $options->get_track_content() );
	}

	public function test_construct_with_full_request() {
		$settings = new \WpMatomo\Settings();
		$request  = [
			'track_api_endpoint'        => 'test value 1',
			'force_protocol'            => 'test value 2',
			'track_js_endpoint'         => 'test value 3',
			'set_download_extensions'   => 'test value 4',
			'add_download_extensions'   => 'test value 5',
			'set_download_classes'      => 'test value 6',
			'set_link_classes'          => 'test value 7',
			'disable_cookies'           => 'test value 8',
			'track_crossdomain_linking' => 'test value 9',
			'track_jserrors'            => 'test value 10',
			'track_across'              => 'test value 11',
			'track_across_alias'        => 'test value 12',
			'force_post'                => 'test value 13',
			'cookie_consent'            => 'test value 14',
			'limit_cookies'             => 'test value 15',
			'limit_cookies_visitor'     => 'test value 16',
			'limit_cookies_referral'    => 'test value 17',
			'limit_cookies_session'     => 'test value 18',
			'track_heartbeat'           => 'test value 19',
			'track_datacfasync'         => 'test value 20',
			'track_content'             => 'test value 21',
		];

		$options = new GeneratorOptions( $settings, $request );

		$this->assertEquals( 'test value 1', $options->get_track_api_endpoint() );
		$this->assertEquals( 'test value 2', $options->get_force_protocol() );
		$this->assertEquals( 'test value 3', $options->get_track_js_endpoint() );
		$this->assertEquals( 'test value 4', $options->get_set_download_extensions() );
		$this->assertEquals( 'test value 5', $options->get_add_download_extensions() );
		$this->assertEquals( 'test value 6', $options->get_set_download_classes() );
		$this->assertEquals( 'test value 7', $options->get_set_link_classes() );
		$this->assertEquals( 'test value 8', $options->get_disable_cookies() );
		$this->assertEquals( 'test value 9', $options->get_track_crossdomain_linking() );
		$this->assertEquals( 'test value 10', $options->get_track_jserrors() );
		$this->assertEquals( 'test value 11', $options->get_track_across() );
		$this->assertEquals( 'test value 12', $options->get_track_across_alias() );
		$this->assertEquals( 'test value 13', $options->get_force_post() );
		$this->assertEquals( 'test value 14', $options->get_cookie_consent() );
		$this->assertEquals( 'test value 15', $options->get_limit_cookies() );
		$this->assertEquals( 'test value 16', $options->get_limit_cookies_visitor() );
		$this->assertEquals( 'test value 17', $options->get_limit_cookies_referral() );
		$this->assertEquals( 'test value 18', $options->get_limit_cookies_session() );
		$this->assertEquals( 'test value 19', $options->get_track_heartbeat() );
		$this->assertEquals( 'test value 20', $options->get_track_datacfasync() );
		$this->assertEquals( 'test value 21', $options->get_track_content() );
	}
}
