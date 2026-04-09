<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo;

use WpMatomo\Admin\Menu;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class PluginActionLinks extends Feature {

	/**
	 * @var Settings
	 */
	private $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	public function register_hooks() {
		add_filter(
			'plugin_action_links_' . plugin_basename( MATOMO_ANALYTICS_FILE ),
			[
				$this,
				'add_settings_link',
			]
		);
	}
	public function add_settings_link( $links ) {
		$get_started = new \WpMatomo\Admin\GetStarted( $this->settings );

		if ( $this->settings->get_global_option( Settings::SHOW_GET_STARTED_PAGE ) && $get_started->can_user_manage() ) {
			$links[] = '<a href="' . menu_page_url( Menu::SLUG_GET_STARTED, false ) . '">' . __( 'Get Started', 'matomo' ) . '</a>';
		} elseif ( current_user_can( Capabilities::KEY_SUPERUSER ) ) {
			$links[] = '<a href="' . menu_page_url( Menu::SLUG_SETTINGS, false ) . '">' . __( 'Settings', 'matomo' ) . '</a>';
		}

		return $links;
	}
}
