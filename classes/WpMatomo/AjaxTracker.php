<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo;

use WpMatomo\Ecommerce\ServerSideVisitorId;
use WpMatomo\TrackingCode\GeneratorOptions;
use WpMatomo\TrackingCode\TrackingCodeGenerator;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

if ( ! class_exists( '\PiwikTracker' ) ) {
	include_once plugin_dir_path( MATOMO_ANALYTICS_FILE ) . 'app/vendor/matomo/matomo-php-tracker/MatomoTracker.php';
}

class AjaxTracker extends \MatomoTracker {
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
		$this->urlReferrer = false;
		$this->pageUrl     = ! empty( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : false;

		if ( ! $settings->get_global_option( 'disable_cookies' ) ) {
			$tracking_code_generator = new TrackingCodeGenerator( $settings, new GeneratorOptions( $settings ) );
			$cookie_domain = $tracking_code_generator->get_tracking_cookie_domain();
			$this->enableCookies( $cookie_domain );
		} else {
			$this->disableCookieSupport();
		}

		$has_wc_session = function_exists( 'WC' ) && isset( WC()->session );
		if ( $has_wc_session ) {
			$visitor_id = WC()->session->get( ServerSideVisitorId::VISITOR_ID_SESSION_VAR_NAME );
			$this->logger->log("setting visitor ID from session: " . $visitor_id);
			if ( ! empty( $visitor_id ) ) {
				$this->hasCookie = true; // do not set cookies for this visitor
				try {
					$this->setVisitorId( $visitor_id );
				} catch ( \Exception $ex ) {
					// do not fatal if the visitor ID is invalid for some reason
					if ( ! $this->is_invalid_visitor_id_error( $ex ) ) {
						throw $ex;
					}
				}
			}
		}

		if ( empty( $this->forcedVisitorId ) && $this->loadVisitorIdCookie() ) {
			$this->logger->log("setting visitor ID from cookie: " . $this->cookieVisitorId);
			if ( ! empty( $this->cookieVisitorId ) ) {
				$this->has_cookie = true;
				try {
					$this->setVisitorId( $this->cookieVisitorId );

					// save first used visitor ID for this session so we can keep using it, in case
					// the visitor ID cookie in a user's browser changes (either due to developer error
					// or some strange configuration we don't know about)
					if ( $has_wc_session ) {
						WC()->session->set( ServerSideVisitorId::VISITOR_ID_SESSION_VAR_NAME, $this->cookieVisitorId );
					}
				} catch (\Exception $ex) {
					// do not fatal if the visitor ID is invalid for some reason
					if ( ! $this->is_invalid_visitor_id_error( $ex ) ) {
						throw $ex;
					}
				}
			}
		}
	}

	protected function setCookie( $cookieName, $cookieValue, $cookieTTL ) {
		if ( ! $this->has_cookie ) {
			// we only set / overwrite cookies if it is a visitor that has eg no JS enabled or ad blocker enabled etc.
			// this way we will track all cart updates and orders into the same visitor on following requests.
			// If we recognized the visitor before via cookie we want in our case to make sure to not overwrite
			// any cookie
			parent::setCookie( $cookieName, $cookieValue, $cookieTTL );
		}
	}

	protected function sendRequest( $url, $method = 'GET', $data = null, $force = false ) {
		if ( ! $this->idSite ) {
			$this->logger->log( 'ecommerce tracking could not find idSite, cannot send request' );
			return; // not installed or synced yet
		}
		$args = array(
			'method' => $method,
		);
		if ( ! empty( $data ) ) {
			$args['body'] = $data;
		}

		// todo at some point we could think about including `matomo.php` here instead of doing an http request
		// however we would need to make sure to set a custom tracker response handler to
		// 1) Not send any response no matter what happens
		// 2) Never exit at any point

		$url = $url . '&bots=1';

		$this->logger->log( 'AjaxTracker: wp_remote_request to ' . $url );

		$response = wp_remote_request( $url, $args );

		$this->logger->log( 'AjaxTracker: wp_remote_request response is ' . json_encode( $response ) );

		if ( is_wp_error( $response ) ) {
			$this->logger->log_exception( 'ajax_tracker', new \Exception( $response->get_error_message() ) );
		}

		return $response;
	}

	private function is_invalid_visitor_id_error( \Exception $ex ) {
		return strpos( $ex->getMessage(), 'setVisitorId() expects' ) === 0;
	}
}
