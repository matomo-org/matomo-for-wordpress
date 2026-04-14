<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo\Admin\PluginSuggestions;

use WpMatomo\Admin\PluginSuggestions\Suggestions\AdvertisingConversionExport;
use WpMatomo\Admin\PluginSuggestions\Suggestions\Funnels;
use WpMatomo\Admin\PluginSuggestions\Suggestions\HeatmapSessionRecording;
use WpMatomo\Admin\PluginSuggestions\Suggestions\SearchEngineKeywordsPerformance;
use WpMatomo\Admin\PluginSuggestions\Suggestions\UsersFlow;
use WpMatomo\Admin\PluginSuggestions\Suggestions\WpPremiumBundle;
use WpMatomo\Bootstrap;
use WpMatomo\Feature;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class PluginSuggestions extends Feature {

	const SUGGESTION_TRIGGERED_OPTION_NAME  = 'matomo_plugin_suggestion_to_show';
	const DISMISSED_SUGGESTIONS_OPTION_NAME = 'matomo_dismissed_suggestions';
	const FORCE_SUGGESTION_QUERY_PARAM_NAME = 'mtm_force_suggestion';
	const DISMISS_SUGGESTION_NONCE          = 'matomo_dismiss_suggestion';

	public function register_hooks() {
		add_action( 'matomo_scheduled_check_plugin_suggestions', [ $this, 'check' ], 10 );

		if ( wp_next_scheduled( 'matomo_scheduled_check_plugin_suggestions' ) === false ) {
			wp_schedule_event( time(), 'daily', 'matomo_scheduled_check_plugin_suggestions', [], true );
		}

		add_action( 'matomo_page_content_before', [ $this, 'show' ] );

		add_action( 'admin_enqueue_scripts', [ $this, 'load_scripts' ] );

		foreach ( $this->get_suggestions() as $suggestion ) {
			$suggestion->register_hooks();
		}
	}

	public function register_ajax() {
		add_action( 'wp_ajax_matomo_dismiss_suggestion', [ $this, 'dismiss_suggestion_ajax' ] );
	}

	public function load_scripts() {
		wp_localize_script(
			'matomo-admin-js',
			'mtmDismissSuggestionAjax',
			[
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( self::DISMISS_SUGGESTION_NONCE ),
			]
		);
	}

	public function show() {
		if ( ! empty( $_REQUEST[ self::FORCE_SUGGESTION_QUERY_PARAM_NAME ] ) ) {
			$matomo_suggestion_to_show = sanitize_text_field( wp_unslash( $_REQUEST[ self::FORCE_SUGGESTION_QUERY_PARAM_NAME ] ) );
		} else {
			$matomo_suggestion_to_show = get_user_option( self::SUGGESTION_TRIGGERED_OPTION_NAME );
		}

		$matomo_suggestion_to_show = $this->find_suggestion_by_class( $matomo_suggestion_to_show );

		if ( empty( $matomo_suggestion_to_show ) ) {
			return;
		}

		if ( $this->is_suggestion_dismissed( $matomo_suggestion_to_show ) ) {
			return;
		}

		require __DIR__ . '/views/suggestion.php';
	}

	public function check() {
		Bootstrap::do_bootstrap();

		$triggered_suggestions = [];

		foreach ( $this->get_suggestions() as $suggestion ) {
			if ( ! $suggestion->is_suggestion_applicable() ) {
				continue;
			}

			$should_trigger = \Piwik\Access::doAsSuperUser(
				function () use ( $suggestion ) {
					return $suggestion->should_trigger();
				}
			);
			if ( ! $should_trigger ) {
				continue;
			}

			$triggered_suggestions[] = $suggestion;
		}

		$users = get_users( [ 'fields' => 'ID' ] );
		foreach ( $users as $user_id ) {
			delete_user_option( $user_id, self::SUGGESTION_TRIGGERED_OPTION_NAME );

			foreach ( $triggered_suggestions as $suggestion ) {
				if ( $this->is_suggestion_dismissed( $suggestion, $user_id ) ) {
					continue;
				}

				update_user_option( $user_id, self::SUGGESTION_TRIGGERED_OPTION_NAME, wp_slash( get_class( $suggestion ) ) );
				break;
			}
		}
	}

	/**
	 * @param string $suggestion_id simple or full suggestion class name
	 * @return void
	 */
	public function dismiss_suggestion( $suggestion_id ) {
		$suggestion = $this->find_suggestion_by_class( $suggestion_id );
		if ( empty( $suggestion ) ) {
			return;
		}

		$dismissed_suggestions = get_user_option( self::DISMISSED_SUGGESTIONS_OPTION_NAME );
		if ( ! is_array( $dismissed_suggestions ) ) {
			$dismissed_suggestions = [];
		}

		// user metadata is unslashed when saving, but is not re-slashed when getting
		$dismissed_suggestions[] = get_class( $suggestion );
		$dismissed_suggestions   = array_map( 'wp_slash', $dismissed_suggestions );
		$dismissed_suggestions   = array_values( array_unique( $dismissed_suggestions ) );

		update_user_option( get_current_user_id(), self::DISMISSED_SUGGESTIONS_OPTION_NAME, $dismissed_suggestions );
	}

	public function dismiss_suggestion_ajax() {
		check_ajax_referer( self::DISMISS_SUGGESTION_NONCE );

		if ( ! empty( $_REQUEST['suggestion'] ) ) {
			$suggestion_id = sanitize_text_field( wp_unslash( $_REQUEST['suggestion'] ) );
			$this->dismiss_suggestion( $suggestion_id );
		}

		wp_send_json( [ 'ok' => true ] );
	}

	/**
	 * @return Suggestion[]
	 */
	private function get_suggestions() {
		// ordered by priority to show
		$suggestions = [
			new HeatmapSessionRecording(),
			new SearchEngineKeywordsPerformance(),
			new AdvertisingConversionExport(),
			new WpPremiumBundle(),
			new UsersFlow(),
			new Funnels(),
		];

		foreach ( $suggestions as $suggestion ) {
			$suggestion->init();
		}

		return $suggestions;
	}

	private function find_suggestion_by_class( $suggestion_to_show ) {
		foreach ( $this->get_suggestions() as $suggestion ) {
			$full_class   = get_class( $suggestion );
			$simple_class = explode( '\\', $full_class );
			$simple_class = end( $simple_class );

			if (
				$full_class === $suggestion_to_show
				|| $simple_class === $suggestion_to_show
			) {
				return $suggestion;
			}
		}
		return null;
	}

	private function is_suggestion_dismissed( Suggestion $suggestion, $user = 0 ) {
		$dismissed_suggestions = get_user_option( self::DISMISSED_SUGGESTIONS_OPTION_NAME, $user );
		if ( ! is_array( $dismissed_suggestions ) ) {
			$dismissed_suggestions = [];
		}
		return in_array( get_class( $suggestion ), $dismissed_suggestions, true );
	}
}
