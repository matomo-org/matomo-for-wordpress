<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo\AbTest;

class UrlDecorator {

	/**
	 * @var UrlDecorator|null
	 */
	private static $global_instance = null;

	/**
	 * @var AbTestStorage
	 */
	private $ab_test_storage;

	public function __construct( AbTestStorage $ab_test_storage ) {
		$this->ab_test_storage = $ab_test_storage;
	}

	public function decorate_url( $url ) {
		if ( false === strpos( $url, '?' ) ) {
			$url .= '?';
		} elseif ( '&' !== substr( $url, -1 ) ) {
			$url .= '&';
		}

		$url .= 'wp=1';

		$active_experiments = $this->ab_test_storage->get_active_ab_tests();
		$url               .= '&mtm_exp=' . rawurlencode( wp_json_encode( $active_experiments ) );

		return $url;
	}

	public static function decorate( $url ) {
		if ( empty( self::$global_instance ) ) {
			self::$global_instance = new UrlDecorator( AbTestStorage::get_global_instance() );
		}

		return self::$global_instance->decorate_url( $url );
	}
}
