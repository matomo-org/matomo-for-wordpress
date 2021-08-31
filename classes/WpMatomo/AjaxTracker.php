<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo;

use Exception;
use MatomoTracker;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

if ( ! class_exists( '\PiwikTracker' ) ) {
	include_once plugin_dir_path( MATOMO_ANALYTICS_FILE ) . 'app/vendor/matomo/matomo-php-tracker/MatomoTracker.php';
}

class AjaxTracker extends MatomoTracker {
	private $has_cookie = false;
	private $logger;

	public function __construct( Settings $settings ) {
		$this->logger = new Logger();

		$site   = new Site();
		$idsite = $site->get_current_matomo_site_id();

		if ( ! $idsite ) {
			return;
		}

		$paths = new Paths();

		if ( $settings->get_global_option( 'track_api_endpoint' ) === 'restapi' ) {
			$api_endpoint = $paths->get_tracker_api_rest_api_endpoint();
		} else {
			$api_endpoint = $paths->get_tracker_api_url_in_matomo_dir();
		}

		parent::__construct( $idsite, $api_endpoint );

		// we are using the tracker only in ajax so the referer contains the actual url
		// @todo deprecated ?
		$this->url_referrer = false;
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$this->page_url = ! empty( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : false;

		if ( ! $settings->get_global_option( 'disable_cookies' ) ) {
			$cookie_domain = $settings->get_tracking_cookie_domain();
			$this->enableCookies( $cookie_domain );
		}

		if ( $this->loadVisitorIdCookie() ) {
			if ( ! empty( $this->cookie_visitor_id ) ) {
				$this->has_cookie = true;
				$this->setVisitorId( $this->cookie_visitor_id );
			}
		}
	}

	protected function setCookie( $cookie_name, $cookie_value, $cookie_ttl ) {
		if ( ! $this->has_cookie ) {
			// we only set / overwrite cookies if it is a visitor that has eg no JS enabled or ad blocker enabled etc.
			// this way we will track all cart updates and orders into the same visitor on following requests.
			// If we recognized the visitor before via cookie we want in our case to make sure to not overwrite
			// any cookie
			parent::setCookie( $cookie_name, $cookie_value, $cookie_ttl );
		}
	}

	protected function sendRequest( $url, $method = 'GET', $data = null, $force = false ) {
		if ( ! $this->id_site ) {
			$this->logger->log( 'ecommerce tracking could not find idSite, cannot send request' );

			return; // not installed or synced yet
		}
		$args = [
			'method' => $method,
		];
		if ( ! empty( $data ) ) {
			$args['body'] = $data;
		}

		// todo at some point we could think about including `matomo.php` here instead of doing an http request
		// however we would need to make sure to set a custom tracker response handler to
		// 1) Not send any response no matter what happens
		// 2) Never exit at any point

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			$this->logger->log_exception( 'ajax_tracker', new Exception( $response->get_error_message() ) );
		}

		return $response;
	}
}
