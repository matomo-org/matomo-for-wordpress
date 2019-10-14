<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace WpMatomo\Marketplace;

use WpMatomo\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class Api {
	private $endpoint = 'https://plugins.matomo.org';

	/**
	 * @var Settings
	 */
	private $settings;

	/**
	 * @param Settings $settings
	 */
	public function __construct( $settings ) {
		$this->settings = $settings;
	}

	public function is_valid_api_key( $license_key ) {

		$looks_valid_format = ctype_alnum( $license_key )
		                      && strlen( $license_key ) >= 40
		                      && strlen( $license_key ) <= 80;
		if ( ! $looks_valid_format ) {
			return false;
		}

		if ( ! empty( $license_key ) ) {
			$result = $this->request_api( 'consumer/validate', array(
				'access_token' => $license_key
			) );

			return ! empty( $result['isValid'] );
		}

		return false;
	}

	public function get_licenses() {
		$result = $this->request_api( 'consumer', array() );
		if ( ! empty( $result['licenses'] ) ) {
			return $result['licenses'];
		}

		return [];
	}

	private function request_api( $path, $request ) {
		if ( empty( $request['access_token'] ) && $this->settings->get_license_key() ) {
			$request['access_token'] = $this->settings->get_license_key();
		}

		$result = wp_remote_post( $this->endpoint . '/api/2.0/' . $path, array(
			'method'      => 'POST',
			'timeout'     => 30,
			'redirection' => 2,
			'body'        => $request
		) );

		if ( is_wp_error( $result ) ) {
			throw new \Exception( $result->get_error_message() );
		}

		return json_decode( $result['body'], true );
	}
}
