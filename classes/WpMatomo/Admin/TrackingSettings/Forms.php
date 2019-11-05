<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 * Code Based on
 * @author Andr&eacute; Br&auml;kling
 * @package WP_Matomo
 * https://github.com/braekling/matomo
 *
 */

namespace WpMatomo\Admin\TrackingSettings;

use WpMatomo\Admin\TrackingSettings;
use WpMatomo\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

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
	 * @param string $id option id
	 * @param string $description option description
	 * @param boolean $hideDescription set to false to show description initially (default: true)
	 *
	 * @return string full description HTML
	 */
	public function get_description( $id, $description, $hideDescription = true ) {
		$title = __('Click to read help', 'matomo');
		return sprintf( '<span class="dashicons dashicons-editor-help" title="%1$s" style="cursor: pointer;" onclick="jQuery(\'#%2$s-desc\').toggleClass(\'hidden\');"></span> <p class="description' . ( $hideDescription ? ' hidden' : '' ) . '" id="%2$s-desc">%3$s</p>', $title, $id, $description );
	}

	/**
	 * Show a checkbox option
	 *
	 * @param string $id option id
	 * @param string $name descriptive option name
	 * @param string $description option description
	 * @param boolean $isHidden set to true to initially hide the option (default: false)
	 * @param string $groupName define a class name to access a group of option rows by javascript (default: empty)
	 * @param boolean $hideDescription $hideDescription set to false to show description initially (default: true)
	 * @param string $onChange javascript for onchange event (default: empty)
	 */
	public function show_checkbox( $id, $name, $description, $isHidden = false, $groupName = '', $hideDescription = true, $onChange = '' ) {
		printf( '<tr class="' . $groupName . ( $isHidden ? ' hidden' : '' ) . '"><th scope="row"><label for="%2$s">%s</label>:</th><td><input type="checkbox" value="1"' . ( $this->settings->get_global_option( $id ) ? ' checked="checked"' : '' ) . ' onchange="jQuery(\'#%s\').val(this.checked?1:0);%s" /><input id="%2$s" type="hidden" name="' . TrackingSettings::FORM_NAME . '[%2$s]" value="' . ( int ) $this->settings->get_global_option( $id ) . '" /> %s</td></tr>', esc_html($name), $id, $onChange, $this->get_description( $id, $description, $hideDescription ) );
	}

	/**
	 * Show a textarea option
	 *
	 * @param string $id option id
	 * @param string $name descriptive option name
	 * @param int $rows number of rows to show
	 * @param string $description option description
	 * @param boolean $isHidden set to true to initially hide the option (default: false)
	 * @param string $groupName define a class name to access a group of option rows by javascript (default: empty)
	 * @param boolean $hideDescription $hideDescription set to false to show description initially (default: true)
	 * @param string $onChange javascript for onchange event (default: empty)
	 * @param boolean $isReadonly set textarea to read only (default: false)
	 * @param boolean $global set to false if the textarea shows a site-specific option (default: true)
	 */
	public function show_textarea( $id, $name, $rows, $description, $isHidden, $groupName, $hideDescription = true, $onChange = '', $isReadonly = false, $global = true ) {
		printf(
			'<tr class="' . $groupName . ( $isHidden ? ' hidden' : '' ) . '"><th scope="row"><label for="%2$s">%s</label>:</th><td><textarea cols="80" rows="' . $rows . '" id="%s" name="' . TrackingSettings::FORM_NAME . '[%2$s]" onchange="%s"' . ( $isReadonly ? ' readonly="readonly"' : '' ) . '>%s</textarea> %s</td></tr>', esc_html($name), $id, $onChange, ( $global ? $this->settings->get_global_option( $id ) : $this->settings->get_option( $id ) ), $this->get_description( $id, $description, $hideDescription ) );
	}

	/**
	 * Show a simple text
	 *
	 * @param string $text Text to show
	 */
	public function show_text( $text ) {
		printf( '<tr><td colspan="2"><p>%s</p></td></tr>', esc_html($text) );
	}

	/**
	 * Show an input option
	 *
	 * @param string $id option id
	 * @param string $name descriptive option name
	 * @param string $description option description
	 * @param boolean $isHidden set to true to initially hide the option (default: false)
	 * @param string $groupName define a class name to access a group of option rows by javascript (default: empty)
	 * @param string $rowName define a class name to access the specific option row by javascript (default: empty)
	 * @param boolean $hideDescription $hideDescription set to false to show description initially (default: true)
	 * @param boolean $wide Create a wide box (default: false)
	 */
	public function show_input( $id, $name, $description, $isHidden = false, $groupName = '', $rowName = false, $hideDescription = true, $wide = false ) {
		printf( '<tr class="%s%s"%s><th scope="row"><label for="%5$s">%s:</label></th><td><input ' . ( $wide ? 'class="matomo-wide" ' : '' ) . 'name="' . TrackingSettings::FORM_NAME . '[%s]" id="%5$s" value="%s" /> %s</td></tr>', $isHidden ? 'hidden ' : '', $groupName ? $groupName : '', $rowName ? ' id="' . $groupName . '-' . $rowName . '"' : '', esc_html($name), $id, htmlentities( $this->settings->get_global_option( $id ), ENT_QUOTES, 'UTF-8', false ), ! empty( $description ) ? $this->get_description( $id, $description, $hideDescription ) : '' );
	}

	/**
	 * Show a select box option
	 *
	 * @param string $id option id
	 * @param string $name descriptive option name
	 * @param array $options list of options to show array[](option id => descriptive name)
	 * @param string $description option description
	 * @param string $onChange javascript for onchange event (default: empty)
	 * @param boolean $isHidden set to true to initially hide the option (default: false)
	 * @param string $groupName define a class name to access a group of option rows by javascript (default: empty)
	 * @param boolean $hideDescription $hideDescription set to false to show description initially (default: true)
	 * @param boolean $global set to false if the textarea shows a site-specific option (default: true)
	 */
	public function show_select( $id, $name, $options = array(), $description = '', $onChange = '', $isHidden = false, $groupName = '', $hideDescription = true, $global = true ) {
		$options_list = '';
		$default    = $global ? $this->settings->get_global_option( $id ) : $this->settings->get_option( $id );
		if ( is_array( $options ) ) {
			foreach ( $options as $key => $value ) {
				$options_list .= sprintf( '<option value="%s"' . ( $key == $default ? ' selected="selected"' : '' ) . '>%s</option>', esc_attr($key), esc_html($value) );
			}
		}
		$script_change = '';
		if ($onChange) {
			// we make sure it will select the right settings by default
			$script_change .= '<script type="text/javascript">setTimeout(function () { jQuery("#'.esc_js($id).'").change(); }, 800);</script>';
		}
		printf( '<tr class="' . $groupName . ( $isHidden ? ' hidden' : '' ) . '"><th scope="row"><label for="%3$s">%s:%s</label></th><td><select name="' . TrackingSettings::FORM_NAME . '[%s]" id="%3$s" onchange="%s">%s</select> %s</td></tr>', $name, $script_change, $id, $onChange, $options_list, $this->get_description( $id, $description, $hideDescription ) );
	}

	/**
	 * Show an info box
	 *
	 * @param string $type box style (e.g., updated, error)
	 * @param string $icon box icon, see https://developer.wordpress.org/resource/dashicons/
	 * @param string $content box message
	 */
	public function show_box( $type, $icon, $content ) {
		printf( '<tr><td colspan="2"><div class="%s"><p><span class="dashicons dashicons-%s"></span> %s</p></div></td></tr>', esc_attr($type), esc_attr($icon), esc_html($content) );
	}

}
