<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

/**
 * Prevents this plugin from being updated to a version that cannot run on this server.
 */
class MinimumRequirementsUpdateGuard extends Feature {

	const ERROR_CODE = 'matomo_minimum_requirements_not_met';

	/**
	 * Matches the package URLs wordpress.org serves, eg
	 * https://downloads.wordpress.org/plugin/matomo.6.0.0.zip
	 */
	const PACKAGE_URL_REGEX = '#//downloads\.wordpress\.org/plugin/matomo\.(\d[\w.\-]*)\.zip#i';

	/**
	 * @var MinimumRequirements
	 */
	private $requirements;

	/**
	 * @param MinimumRequirements|null $requirements
	 */
	public function __construct( $requirements = null ) {
		$this->requirements = $requirements ? $requirements : new MinimumRequirements();
	}

	public function is_active() {
		return true; // always active
	}

	public function register_hooks() {
		add_filter( 'upgrader_pre_download', [ $this, 'block_download_if_incompatible' ], 10, 4 );
		add_filter( 'auto_update_plugin', [ $this, 'disable_auto_update_if_incompatible' ], 10, 2 );

		if ( is_admin() ) {
			add_action(
				// phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
				'in_plugin_update_message-' . $this->get_plugin_basename(),
				[ $this, 'show_update_row_message' ],
				10,
				2
			);
		}
	}

	public function remove_hooks() {
		remove_filter( 'upgrader_pre_download', [ $this, 'block_download_if_incompatible' ], 10 );
		remove_filter( 'auto_update_plugin', [ $this, 'disable_auto_update_if_incompatible' ], 10 );
		// phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
		remove_action( 'in_plugin_update_message-' . $this->get_plugin_basename(), [ $this, 'show_update_row_message' ], 10 );
	}

	/**
	 * Short circuits the download of a Matomo package this server cannot run.
	 *
	 * @param false|string|WP_Error $reply
	 * @param string                $package
	 * @param mixed                 $upgrader
	 * @param array                 $hook_extra
	 * @return false|string|WP_Error
	 */
	public function block_download_if_incompatible( $reply, $package, $upgrader = null, $hook_extra = [] ) {
		if ( false !== $reply ) {
			return $reply; // somebody else already handled this download
		}

		if ( ! $this->is_matomo_package( $package, $hook_extra ) ) {
			return $reply;
		}

		$target_version = $this->get_target_version( $package, $hook_extra );

		$unmet = $this->get_unmet_requirements_for_version( $target_version );
		if ( empty( $unmet ) ) {
			return $reply;
		}

		return new WP_Error( self::ERROR_CODE, $this->get_error_message( $target_version, $unmet ) );
	}

	/**
	 * Stops the background updater from repeatedly attempting an update that is going to be
	 * blocked anyway, which would send the site admin a failure email every time.
	 *
	 * @param bool  $update
	 * @param mixed $item
	 * @return bool
	 */
	public function disable_auto_update_if_incompatible( $update, $item ) {
		if ( empty( $item ) || ! is_object( $item ) ) {
			return $update;
		}

		if ( empty( $item->plugin ) || $this->get_plugin_basename() !== $item->plugin ) {
			return $update;
		}

		$new_version = isset( $item->new_version ) ? $item->new_version : '';
		if ( $this->get_unmet_requirements_for_version( $new_version ) ) {
			return false;
		}

		return $update;
	}

	/**
	 * Explains on the plugins screen why the available update cannot be installed, so users
	 * find out before clicking "update now".
	 *
	 * @param array  $plugin_data
	 * @param object $response
	 */
	public function show_update_row_message( $plugin_data, $response ) {
		$new_version = ( is_object( $response ) && isset( $response->new_version ) ) ? $response->new_version : '';

		$unmet = $this->get_unmet_requirements_for_version( $new_version );
		if ( empty( $unmet ) ) {
			return;
		}

		// this is rendered inside a <p>, so no block level elements here.
		echo '<br><em style="display: inline-block;margin-top:8px;"><strong>'
			. esc_html__( 'WARNING', 'matomo' ) . ': '
			. '</strong>'
			. esc_html__( 'This update cannot be installed because your server does not meet Matomo\'s new minimum requirements:', 'matomo' )
			. ' '
			. esc_html( implode( ' ', $unmet ) )
			. '</em>';
	}

	/**
	 * @param string $version
	 * @return string[] empty when the version can run on this server or when it is unknown
	 */
	private function get_unmet_requirements_for_version( $version ) {
		if ( empty( $version ) || ! $this->requirements->does_plugin_version_require_new_minimums( $version ) ) {
			return [];
		}

		return $this->requirements->get_unmet_requirements();
	}

	/**
	 * @param string $package
	 * @param array  $hook_extra
	 * @return bool
	 */
	private function is_matomo_package( $package, $hook_extra ) {
		if ( ! empty( $hook_extra['plugin'] ) && $this->get_plugin_basename() === $hook_extra['plugin'] ) {
			return true;
		}

		// installs (and WordPress older than 5.5) do not pass the plugin along, so fall back
		// to recognizing the package wordpress.org serves.
		return (bool) $this->get_matomo_package_version_from_url( $package );
	}

	private function get_matomo_package_version_from_url( $package ) {
		if ( ! empty( $package )
			&& is_string( $package )
			&& preg_match( self::PACKAGE_URL_REGEX, $package, $matches )
		) {
			return $matches[1];
		}

		return null;
	}

	/**
	 * @param string $package
	 * @param array  $hook_extra
	 * @return string empty when the version cannot be determined
	 */
	private function get_target_version( $package, $hook_extra ) {
		$version = $this->get_matomo_package_version_from_url( $package );
		if ( $version ) {
			return $version;
		}

		// 'update_plugins' is the core transient used to cache available plugin updates
		$updates  = get_site_transient( 'update_plugins' );
		$basename = $this->get_plugin_basename();

		if ( ! empty( $updates->response[ $basename ]->new_version ) ) {
			return (string) $updates->response[ $basename ]->new_version;
		}

		// deliberately not blocking a package we cannot identify: a version we cannot read is
		// more likely an unrelated flow than an incompatible Matomo. WpMatomo::is_safe_mode()
		// is the backstop should such a version end up installed.
		return '';
	}

	/**
	 * @param string   $target_version
	 * @param string[] $unmet
	 * @return string
	 */
	private function get_error_message( $target_version, array $unmet ) {
		// note: the finished message must not contain a percent sign, WP_Upgrader_Skin::feedback()
		// runs vsprintf() over any string that does.
		$message = sprintf(
			__( 'Matomo Analytics %s cannot be installed because your server does not meet its minimum requirements:', 'matomo' ),
			$target_version
		);

		$message .= ' ' . implode( ' ', $unmet );
		$message .= ' ' . __( 'Please ask your hosting provider to update your server and then try again.', 'matomo' );
		$message .= ' <a href="' . esc_url( MinimumRequirementsNotice::REQUIREMENTS_FAQ_URL ) . '" target="_blank" rel="noreferrer noopener">'
			. __( 'Learn more about the requirements for Matomo for WordPress', 'matomo' ) . '</a>';

		return $message;
	}

	/**
	 * @return string
	 */
	private function get_plugin_basename() {
		return plugin_basename( MATOMO_ANALYTICS_FILE );
	}
}
