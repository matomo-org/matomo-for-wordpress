<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo;
class RedirectOnActivation {
	/**
	 * @var WpMatomo
	 */
	public $wpMatomo;

	public function __construct(\WpMatomo $wpMatomo) {
		$this->wpMatomo = $wpMatomo;
	}

	public function register_hooks() {
		$file = realpath(dirname(__FILE__).'/../../matomo.php');
		register_activation_hook($file, [ $this, 'matomo_activate' ] );
		add_action( 'admin_init', [ $this, 'matomo_plugin_redirect' ] );
	}

	public function matomo_activate() {
		add_option( 'matomo_plugin_do_activation_redirect', true );
	}

	public function matomo_plugin_redirect() {
		if ( get_option( 'matomo_plugin_do_activation_redirect', false ) ) {
			delete_option( 'matomo_plugin_do_activation_redirect' );
			$this->wpMatomo->redirect_to_getting_started();
		}
	}
}