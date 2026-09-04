<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo\Admin;

use Piwik\IP;
use Piwik\Plugins\SitesManager\API;
use WpMatomo\Bootstrap;
use WpMatomo\Capabilities;
use WpMatomo\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class ExclusionSettings implements AdminSettingsInterface {
	const NONCE_NAME = 'matomo_exclusion';
	const FORM_NAME  = 'matomo_exclusions';

	/**
	 * @var Settings
	 */
	private $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	public function get_title() {
		return esc_html__( 'Exclusions', 'matomo' );
	}

	/**
	 * Whether we are displaying settings for an entire network, or just a single blog's Matomo.
	 *
	 * @return bool
	 */
	public function is_network_wide_screen() {
		return is_network_admin() && $this->settings->is_network_enabled();
	}

	public function get_tracking_filter_option_name() {
		if ( ! $this->settings->is_network_enabled() ) {
			// a single site blog has no network to inherit a setting value from, so there is only one list
			// and it stays where it has always been stored
			return Settings::OPTION_KEY_STEALTH;
		}

		return $this->is_network_wide_screen()
			? Settings::OPTION_KEY_STEALTH
			: Settings::OPTION_KEY_STEALTH_BLOG;
	}

	public function can_user_edit_tracking_filter() {
		if ( $this->is_network_wide_screen() ) {
			return current_user_can( Menu::CAP_NETWORK );
		}

		return current_user_can( Capabilities::KEY_ADMIN );
	}

	public function can_user_edit_exclusions() {
		// currently exclusions are a per-blog only setting, and like the tracking filter beside them
		// they only decide what a blog's own Matomo records: nothing here reaches another blog or
		// ends up running in a visitor's browser, so a Matomo admin of the blog is entitled to it
		return ! $this->is_network_wide_screen() && current_user_can( Capabilities::KEY_ADMIN );
	}

	public function show_settings( $throw_exception = false ) {
		global $wp_roles;
		$settings_errors = [];
		$was_updated     = false;
		try {
			$was_updated = $this->update_if_submitted();
		} catch ( InvalidIpException $e ) {
			$settings_errors[] = $e->getMessage();
			if ( $throw_exception ) {
				throw $e;
			}
		}

		$settings                = $this->settings;
		$is_network_wide_screen  = $this->is_network_wide_screen();
		$can_edit_filter         = $this->can_user_edit_tracking_filter();
		$can_edit_exclusions     = $this->can_user_edit_exclusions();
		$tracking_filter_key     = $this->get_tracking_filter_option_name();
		$tracking_filter         = $this->get_stored_tracking_filter( $tracking_filter_key );
		$network_tracking_filter = $is_network_wide_screen || ! $settings->is_network_enabled()
			? []
			: $this->get_stored_tracking_filter( Settings::OPTION_KEY_STEALTH );

		$excluded_ips          = '';
		$excluded_query_params = '';
		$excluded_user_agents  = '';
		$keep_url_fragments    = false;
		$current_ip            = '';

		if ( ! $is_network_wide_screen ) {
			// the network wide screen only shows the tracking filter. the other settings read below
			// are only shown for individual blogs.
			Bootstrap::do_bootstrap();

			$api                   = API::getInstance();
			$excluded_ips          = $this->from_comma_list( $api->getExcludedIpsGlobal() );
			$excluded_query_params = $this->from_comma_list( $api->getExcludedQueryParametersGlobal() );
			$excluded_user_agents  = $this->join_on_newlines( $this->settings->get_global_user_agent_exclusions() );
			$keep_url_fragments    = $api->getKeepURLFragmentsGlobal();
			$current_ip            = $this->get_current_ip();
		}

		include __DIR__ . '/views/exclusion_settings.php';
	}

	private function update_if_submitted() {
		if (
			empty( $_POST[ self::FORM_NAME ] )
			|| ! is_admin()
			|| ! check_admin_referer( self::NONCE_NAME )
		) {
			return false;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$post = wp_unslash( $_POST[ self::FORM_NAME ] );

		$was_updated = false;

		if ( $this->can_user_edit_exclusions() ) {
			$this->update_exclusions( $post );
			$was_updated = true;
		}

		if ( $this->can_user_edit_tracking_filter() ) {
			$this->update_tracking_filter( $post );
			$was_updated = true;
		}

		return $was_updated;
	}

	/**
	 * @param array $post
	 */
	private function update_tracking_filter( $post ) {
		$key = $this->get_tracking_filter_option_name();

		$roles = [];
		if ( ! empty( $post[ $key ] ) && is_array( $post[ $key ] ) ) {
			$roles = $post[ $key ];
		}

		$this->settings->apply_changes( [ $key => $roles ] );
	}

	/**
	 * @param array $post
	 *
	 * @throws InvalidIpException When Matomo refuses one of the excluded IPs.
	 */
	private function update_exclusions( $post ) {
		Bootstrap::do_bootstrap();

		// Matomo asks for super user access on these setters because "global" means every site of an
		// install there. a WP blog has an install of its own holding the one site, so global is this
		// blog and no other, and a Matomo admin of it is entitled to the change
		// (see can_user_edit_exclusions()).
		\Piwik\Access::doAsSuperUser(
			function () use ( $post ) {
				$this->apply_matomo_exclusions( $post );
			}
		);

		$this->apply_user_agent_exclusions( $post );
	}

	/**
	 * @param array $post
	 */
	private function apply_user_agent_exclusions( $post ) {
		if ( ! isset( $post['excluded_user_agents'] ) ) {
			return;
		}

		$useragents = $this->split_on_newlines( $post['excluded_user_agents'] );
		if ( $useragents !== $this->settings->get_global_user_agent_exclusions() ) {
			$this->settings->set_global_user_agent_exclusions( $useragents );
			$this->settings->save();
		}
	}

	/**
	 * @param array $post
	 *
	 * @throws InvalidIpException When Matomo refuses one of the excluded IPs.
	 */
	private function apply_matomo_exclusions( $post ) {
		$api = API::getInstance();
		if ( isset( $post['excluded_ips'] ) ) {
			$ips = $this->to_comma_list( $post['excluded_ips'] );
			if ( $ips !== $api->getExcludedIpsGlobal() ) {
				try {
					$api->setGlobalExcludedIps( $ips );
				} catch ( \Exception $e ) {
					// not escaped here on purpose, the message is escaped where it is rendered
					// (see views/settings_errors.php)
					// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
					throw new InvalidIpException( $e->getMessage() );
				}
			}
		}

		if ( isset( $post['excluded_query_parameters'] ) ) {
			$params = $this->to_comma_list( $post['excluded_query_parameters'] );
			if ( $params !== $api->getExcludedQueryParametersGlobal() ) {
				$api->setGlobalExcludedQueryParameters( $params );
			}
		}

		$keep_fragments = ! empty( $post['keep_url_fragments'] );
		// phpcs:ignore Universal.Operators.StrictComparisons.LooseNotEqual
		if ( $keep_fragments != $api->getKeepURLFragmentsGlobal() ) {
			$api->setKeepURLFragmentsGlobal( $keep_fragments );
		}
	}

	/**
	 * @param string $key
	 *
	 * @return array<string, bool>
	 */
	private function get_stored_tracking_filter( $key ) {
		$roles = Settings::OPTION_KEY_STEALTH === $key
			? $this->settings->get_global_option( $key )
			: $this->settings->get_option( $key );

		return is_array( $roles ) ? $roles : [];
	}

	/**
	 * @param array $value
	 * @return string
	 */
	private function split_on_newlines( $value ) {
		if ( empty( $value ) ) {
			return [];
		}

		$value = stripslashes( $value ); // WordPress adds slashes
		$value = str_replace( "\r", '', $value );
		$value = array_filter( explode( "\n", $value ) );
		return $value;
	}

	/**
	 * @param array $value
	 * @return string
	 */
	private function join_on_newlines( $value ) {
		if ( empty( $value ) ) {
			return '';
		}

		return implode( "\n", array_filter( $value ) );
	}

	private function to_comma_list( $value ) {
		$value = $this->split_on_newlines( $value );
		return implode( ',', $value );
	}

	private function from_comma_list( $value ) {
		return $this->join_on_newlines( explode( ',', $value ) );
	}

	/**
	 * do not sanitize $_SERVER variables
	 * phpcs:disable WordPress.Security.ValidatedSanitizedInput
	 *
	 * @return mixed|string
	 */
	private function get_current_ip() {
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = IP::getIpFromHeader();
		}

		return $ip;
	}
}
