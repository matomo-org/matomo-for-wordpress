<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 * Code Based on
 * @author Andr&eacute; Br&auml;kling
 * https://github.com/braekling/matomo
 *
 */

namespace WpMatomo\Admin\TrackingSettings;

use Piwik\Config;
use WpMatomo;
use WpMatomo\Admin\TrackingSettings;
use WpMatomo\Bootstrap;
use WpMatomo\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}
/**
 * we deal with HTML
 * phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
 */
class Forms {
	/**
	 * @var Settings
	 */
	private $settings;

	public function __construct( $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Show an option's description
	 *
	 * @param string  $id option id
	 * @param string  $description option description
	 * @param boolean $hide_description set to false to show description initially (default: true)
	 *
	 * @return string full description HTML
	 */
	public function get_description( $id, $description, $hide_description = true ) {
		$title = esc_html__( 'Click to read help', 'matomo' );

		return sprintf( '<span class="dashicons dashicons-editor-help" title="%1$s" style="cursor: pointer;" onclick="jQuery(\'#%2$s-desc\').toggleClass(\'hidden\');"></span> <p class="description' . ( $hide_description ? ' hidden' : '' ) . '" id="%2$s-desc">%3$s</p>', $title, esc_attr( $id ), $description );
	}

	/**
	 * Show a checkbox option
	 *
	 * @param string  $id option id
	 * @param string  $name descriptive option name
	 * @param string  $description option description
	 * @param boolean $is_hidden set to true to initially hide the option (default: false)
	 * @param string  $group_name define a class name to access a group of option rows by javascript (default: empty)
	 * @param boolean $hide_description $hideDescription set to false to show description initially (default: true)
	 * @param string  $on_change javascript for onchange event (default: empty)
	 */
	public function show_checkbox( $id, $name, $description, $is_hidden = false, $group_name = '', $hide_description = true, $on_change = '' ) {
		printf( '<tr class="' . esc_attr( $group_name ) . ( $is_hidden ? ' hidden' : '' ) . '"><th scope="row"><label for="%2$s">%s</label>:</th><td><input type="checkbox" value="1"' . ( $this->settings->get_global_option( $id ) ? ' checked="checked"' : '' ) . ' onchange="jQuery(\'#%s\').val(this.checked?1:0);%s" /><input id="%2$s" type="hidden" name="' . esc_attr( TrackingSettings::FORM_NAME ) . '[%2$s]" value="' . (int) $this->settings->get_global_option( $id ) . '" /> %s</td></tr>', esc_html( $name ), esc_attr( $id ), $on_change, $this->get_description( $id, $description, $hide_description ) );
	}

	/**
	 * Show a textarea option
	 *
	 * @param string  $id option id
	 * @param string  $name descriptive option name
	 * @param int     $rows number of rows to show
	 * @param string  $description option description
	 * @param boolean $is_hidden set to true to initially hide the option (default: false)
	 * @param string  $group_name define a class name to access a group of option rows by javascript (default: empty)
	 * @param boolean $hide_description $hideDescription set to false to show description initially (default: true)
	 * @param string  $on_change javascript for onchange event (default: empty)
	 * @param boolean $is_readonly set textarea to read only (default: false)
	 * @param boolean $global set to false if the textarea shows a site-specific option (default: true)
	 */
	public function show_textarea( $id, $name, $rows, $description, $is_hidden, $group_name, $hide_description = true, $on_change = '', $is_readonly = false, $global = true ) {
		printf(
			'<tr class="' . esc_attr( $group_name ) . ( $is_hidden ? ' hidden' : '' ) . '"><th scope="row"><label for="%2$s">%s</label>:</th><td><textarea cols="80" rows="' . esc_attr( $rows ) . '" id="%s" name="' . esc_attr( TrackingSettings::FORM_NAME ) . '[%2$s]" onchange="%s"' . ( $is_readonly ? ' readonly="readonly"' : '' ) . '>%s</textarea> %s</td></tr>',
			esc_html( $name ),
			esc_attr( $id ),
			$on_change,
			( $global ? $this->settings->get_global_option( $id ) : $this->settings->get_option( $id ) ),
			$this->get_description( $id, $description, $hide_description )
		);
	}

	/**
	 * Show a simple text
	 *
	 * @param string $text Text to show
	 */
	public function show_text( $text, $group_name = '' ) {
		printf( '<tr class="%s"><td colspan="2"><p>%s</p></td></tr>', esc_attr( $group_name ), esc_html( $text ) );
	}

	/**
	 * Show a simple text
	 *
	 * @param string $text Text to show
	 */
	public function show_headline( $text, $group_name = '' ) {
		printf( '<tr class="%s"><td colspan="2"><h3>%s</h3></td></tr>', esc_attr( $group_name ), esc_html( $text ) );
	}

	/**
	 * Show an input option
	 *
	 * @param string  $id option id
	 * @param string  $name descriptive option name
	 * @param string  $description option description
	 * @param boolean $is_hidden set to true to initially hide the option (default: false)
	 * @param string  $group_name define a class name to access a group of option rows by javascript (default: empty)
	 * @param string  $row_name define a class name to access the specific option row by javascript (default: empty)
	 * @param boolean $hide_description $hideDescription set to false to show description initially (default: true)
	 * @param boolean $wide Create a wide box (default: false)
	 */
	public function show_input( $id, $name, $description, $is_hidden = false, $group_name = '', $row_name = false, $hide_description = true, $wide = false ) {
		printf( '<tr class="%s%s"%s><th scope="row"><label for="%5$s">%s:</label></th><td><input ' . ( $wide ? 'class="matomo-wide" ' : '' ) . 'name="' . esc_attr( TrackingSettings::FORM_NAME ) . '[%s]" id="%5$s" value="%s" /> %s</td></tr>', $is_hidden ? 'hidden ' : '', $group_name ? $group_name : '', $row_name ? ' id="' . $group_name . '-' . $row_name . '"' : '', esc_html( $name ), esc_attr( $id ), htmlentities( $this->settings->get_global_option( $id ), ENT_QUOTES, 'UTF-8', false ), ! empty( $description ) ? $this->get_description( $id, $description, $hide_description ) : '' );
	}

	/**
	 * Show a select box option
	 *
	 * @param string  $id option id
	 * @param string  $name descriptive option name
	 * @param array   $options list of options to show array[](option id => descriptive name)
	 * @param string  $description option description
	 * @param string  $on_change javascript for onchange event (default: empty)
	 * @param boolean $is_hidden set to true to initially hide the option (default: false)
	 * @param string  $group_name define a class name to access a group of option rows by javascript (default: empty)
	 * @param boolean $hide_description $hideDescription set to false to show description initially (default: true)
	 * @param boolean $global set to false if the textarea shows a site-specific option (default: true)
	 */
	public function show_select( $id, $name, $options = [], $description = '', $on_change = '', $is_hidden = false, $group_name = '', $hide_description = true, $global = true ) {
		$options_list = '';

		if ( 'tracker_debug' === $id && ! WpMatomo::is_safe_mode() && ! $this->settings->is_network_enabled() ) {
			Bootstrap::do_bootstrap();
			if ( Config::getInstance()->Tracker['debug'] ) {
				$default = 'always';
			} elseif ( Config::getInstance()->Tracker['debug_on_demand'] ) {
				$default = 'on_demand';
			} else {
				$default = 'disabled';
			}
		} else {
			$default = $global ? $this->settings->get_global_option( $id ) : $this->settings->get_option( $id );
		}
		if ( is_array( $options ) ) {
			foreach ( $options as $key => $value ) {
				// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
				$options_list .= sprintf( '<option value="%s"' . ( $key == $default ? ' selected="selected"' : '' ) . '>%s</option>', esc_attr( $key ), esc_html( $value ) );
			}
		}
		$script_change = '';
		if ( $on_change ) {
			// we make sure it will select the right settings by default
			$script_change .= '<script type="text/javascript">setTimeout(function () { jQuery("#' . esc_js( $id ) . '").change(); }, 800);</script>';
		}
		printf( '<tr class="' . esc_attr( $group_name ) . ( $is_hidden ? ' hidden' : '' ) . '"><th scope="row"><label for="%3$s">%s:%s</label></th><td><select name="' . esc_attr( TrackingSettings::FORM_NAME ) . '[%s]" id="%3$s" onchange="%s">%s</select> %s</td></tr>', esc_html( $name ), $script_change, esc_attr( $id ), $on_change, $options_list, $this->get_description( $id, $description, $hide_description ) );
	}

	/**
	 * Show an info box
	 *
	 * @param string $type box style (e.g., updated, error)
	 * @param string $icon box icon, see https://developer.wordpress.org/resource/dashicons/
	 * @param string $content box message
	 */
	public function show_box( $type, $icon, $content ) {
		printf( '<tr><td colspan="2"><div class="%s"><p><span class="dashicons dashicons-%s"></span> %s</p></div></td></tr>', esc_attr( $type ), esc_attr( $icon ), esc_html( $content ) );
	}
}
