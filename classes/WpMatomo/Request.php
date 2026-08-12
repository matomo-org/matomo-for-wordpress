<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class Request {

	/**
	 * Returns whether this is a plain front end page view, as opposed to wp-admin (which includes
	 * admin-ajax.php and admin-post.php), the REST API, WP-CLI, XML-RPC or cron.
	 *
	 * Note REST_REQUEST is only defined once rest_api_loaded() runs on parse_request, so a REST
	 * request is indistinguishable from a front end one before then.
	 *
	 * @return bool
	 */
	public static function is_frontend() {
		if ( is_admin() ) {
			return false;
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return false;
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return false;
		}

		if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
			return false;
		}

		return ! wp_doing_cron();
	}
}
