<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo\TrackingCode;

use WpMatomo\Settings;

class GeneratorOptions {
	/**
	 * @var string|null
	 */
	private $track_api_endpoint;

	/**
	 * @var string|null
	 */
	private $force_protocol;

	/**
	 * @var string|null
	 */
	private $track_js_endpoint;

	/**
	 * @var string|null
	 */
	private $set_download_extensions;

	/**
	 * @var string|null
	 */
	private $add_download_extensions;

	/**
	 * @var string|null
	 */
	private $set_download_classes;

	/**
	 * @var string|null
	 */
	private $set_link_classes;

	/**
	 * @var bool
	 */
	private $disable_cookies;

	/**
	 * @var bool
	 */
	private $track_crossdomain_linking;

	/**
	 * @var bool
	 */
	private $track_jserrors;

	/**
	 * @var bool
	 */
	private $track_across;

	/**
	 * @var bool
	 */
	private $track_across_alias;

	/**
	 * @var bool
	 */
	private $force_post;

	/**
	 * @var string|null
	 */
	private $cookie_consent;

	/**
	 * @var bool
	 */
	private $limit_cookies;

	/**
	 * @var string|null
	 */
	private $limit_cookies_visitor;

	/**
	 * @var string|null
	 */
	private $limit_cookies_session;

	/**
	 * @var string|null
	 */
	private $limit_cookies_referral;

	/**
	 * @var string|null
	 */
	private $track_content;

	/**
	 * @var string|number|null
	 */
	private $track_heartbeat;

	/**
	 * @var bool
	 */
	private $track_datacfasync;

	public function get_track_datacfasync() {
		return $this->track_datacfasync;
	}

	public function set_track_datacfasync( $track_datacfasync ) {
		$this->track_datacfasync = $track_datacfasync;
	}

	public function get_track_content() {
		return $this->track_content;
	}

	public function set_track_content( $track_content ) {
		$this->track_content = $track_content;
	}

	public function get_track_heartbeat() {
		return $this->track_heartbeat;
	}

	public function set_track_heartbeat( $track_heartbeat ) {
		$this->track_heartbeat = $track_heartbeat;
	}

	public function get_limit_cookies() {
		return $this->limit_cookies;
	}

	public function set_limit_cookies( $limit_cookies ) {
		$this->limit_cookies = $limit_cookies;
	}

	public function get_limit_cookies_visitor() {
		return $this->limit_cookies_visitor;
	}

	public function set_limit_cookies_visitor( $limit_cookies_visitor ) {
		$this->limit_cookies_visitor = $limit_cookies_visitor;
	}

	public function get_limit_cookies_session() {
		return $this->limit_cookies_session;
	}

	public function set_limit_cookies_session( $limit_cookies_session ) {
		$this->limit_cookies_session = $limit_cookies_session;
	}

	public function get_limit_cookies_referral() {
		return $this->limit_cookies_referral;
	}

	public function set_limit_cookies_referral( $limit_cookies_referral ) {
		$this->limit_cookies_referral = $limit_cookies_referral;
	}

	public function get_cookie_consent() {
		return $this->cookie_consent;
	}

	public function set_cookie_consent( $cookie_consent ) {
		$this->cookie_consent = $cookie_consent;
	}

	public function get_force_post() {
		return $this->force_post;
	}

	public function set_force_post( $force_post ) {
		$this->force_post = $force_post;
	}

	public function get_track_across_alias() {
		return $this->track_across_alias;
	}

	public function set_track_across_alias( $track_across_alias ) {
		$this->track_across_alias = $track_across_alias;
	}

	public function get_track_across() {
		return $this->track_across;
	}

	public function set_track_across( $track_across ) {
		$this->track_across = $track_across;
	}

	public function get_track_crossdomain_linking() {
		return $this->track_crossdomain_linking;
	}

	public function set_track_crossdomain_linking( $track_crossdomain_linking ) {
		$this->track_crossdomain_linking = $track_crossdomain_linking;
	}

	public function get_track_jserrors() {
		return $this->track_jserrors;
	}

	public function set_track_jserrors( $track_jserrors ) {
		$this->track_jserrors = $track_jserrors;
	}

	public function get_disable_cookies() {
		return $this->disable_cookies;
	}

	public function set_disable_cookies( $disable_cookies ) {
		$this->disable_cookies = $disable_cookies;
	}

	public function get_set_link_classes() {
		return $this->set_link_classes;
	}

	public function set_set_link_classes( $set_link_classes ) {
		$this->set_link_classes = $set_link_classes;
	}

	public function get_set_download_classes() {
		return $this->set_download_classes;
	}

	public function set_set_download_classes( $set_download_classes ) {
		$this->set_download_classes = $set_download_classes;
	}

	public function set_add_download_extensions( $add_download_extensions ) {
		$this->add_download_extensions = $add_download_extensions;
	}

	public function get_add_download_extensions() {
		return $this->add_download_extensions;
	}

	public function set_track_api_endpoint( $track_api_endpoint ) {
		$this->track_api_endpoint = $track_api_endpoint;
	}

	public function get_track_api_endpoint() {
		return $this->track_api_endpoint;
	}

	public function set_force_protocol( $force_protocol ) {
		$this->force_protocol = $force_protocol;
	}

	public function get_force_protocol() {
		return $this->force_protocol;
	}

	public function get_track_js_endpoint() {
		return $this->track_js_endpoint;
	}

	public function set_track_js_endpoint( $track_js_endpoint ) {
		$this->track_js_endpoint = $track_js_endpoint;
	}

	public function get_set_download_extensions() {
		return $this->set_download_extensions;
	}

	private function set_set_download_extensions( $set_download_extensions ) {
		$this->set_download_extensions = $set_download_extensions;
	}

	/**
	 * @param Settings $settings
	 * @return GeneratorOptions
	 */
	public static function from_settings( Settings $settings ) {
		$options = new GeneratorOptions();
		$options->set_track_api_endpoint( $settings->get_global_option( 'track_api_endpoint' ) );
		$options->set_force_protocol( $settings->get_global_option( 'force_protocol' ) );
		$options->set_track_js_endpoint( $settings->get_global_option( 'track_js_endpoint' ) );
		$options->set_set_download_extensions( $settings->get_global_option( 'set_download_extensions' ) );
		$options->set_add_download_extensions( $settings->get_global_option( 'add_download_extensions' ) );
		$options->set_set_download_classes( $settings->get_global_option( 'set_download_classes' ) );
		$options->set_set_link_classes( $settings->get_global_option( 'set_link_classes' ) );
		$options->set_disable_cookies( $settings->get_global_option( 'disable_cookies' ) );
		$options->set_track_crossdomain_linking( $settings->get_global_option( 'track_crossdomain_linking' ) );
		$options->set_track_jserrors( $settings->get_global_option( 'track_jserrors' ) );
		$options->set_track_across( $settings->get_global_option( 'track_across' ) );
		$options->set_track_across_alias( $settings->get_global_option( 'track_across_alias' ) );
		$options->set_force_post( $settings->get_global_option( 'force_post' ) );
		$options->set_cookie_consent( $settings->get_global_option( 'cookie_consent' ) );
		$options->set_limit_cookies( $settings->get_global_option( 'limit_cookies' ) );
		$options->set_limit_cookies_visitor( $settings->get_global_option( 'limit_cookies_visitor' ) );
		$options->set_limit_cookies_referral( $settings->get_global_option( 'limit_cookies_referral' ) );
		$options->set_limit_cookies_session( $settings->get_global_option( 'limit_cookies_session' ) );
		$options->set_track_heartbeat( $settings->get_global_option( 'track_content' ) );
		$options->set_track_datacfasync( $settings->get_global_option( 'track_datacfasync' ) );
		$options->set_track_content( $settings->get_global_option( 'track_content' ) );
		return $options;
	}

	/**
	 * @param array $request
	 * @return GeneratorOptions
	 */
	public static function from_request( $request ) {
		$options = new GeneratorOptions();
		$options->set_track_api_endpoint( $request['matomo']['track_api_endpoint'] );
		$options->set_force_protocol( $request['matomo']['force_protocol'] );
		$options->set_track_js_endpoint( $request['matomo']['track_js_endpoint'] );
		$options->set_set_download_extensions( $request['matomo']['set_download_extensions'] );
		$options->set_add_download_extensions( $request['matomo']['add_download_extensions'] );
		$options->set_set_download_classes( $request['matomo']['set_download_classes'] );
		$options->set_set_link_classes( $request['matomo']['set_link_classes'] );
		$options->set_disable_cookies( $request['matomo']['disable_cookies'] );
		$options->set_track_crossdomain_linking( $request['matomo']['track_crossdomain_linking'] );
		$options->set_track_jserrors( $request['matomo']['track_jserrors'] );
		$options->set_track_across( $request['matomo']['track_across'] );
		$options->set_track_across_alias( $request['matomo']['track_across_alias'] );
		$options->set_force_post( $request['matomo']['force_post'] );
		$options->set_cookie_consent( $request['matomo']['cookie_consent'] );
		$options->set_limit_cookies( $request['matomo']['limit_cookies'] );
		$options->set_limit_cookies_visitor( $request['matomo']['limit_cookies_visitor'] );
		$options->set_limit_cookies_referral( $request['matomo']['limit_cookies_referral'] );
		$options->set_limit_cookies_session( $request['matomo']['limit_cookies_session'] );
		$options->set_track_heartbeat( $request['matomo']['track_content'] );
		$options->set_track_datacfasync( $request['matomo']['track_datacfasync'] );
		$options->set_track_content( $request['matomo']['track_content'] );
		return $options;
	}
}
