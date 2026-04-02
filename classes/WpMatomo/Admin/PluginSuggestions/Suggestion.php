<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo\Admin\PluginSuggestions;

abstract class Suggestion {

	/**
	 * @var string
	 */
	protected $plugin_name = '';

	/**
	 * @var string
	 */
	protected $trigger_desc_short = '';

	/**
	 * @var string
	 */
	protected $trigger_desc_long = '';

	/**
	 * @var string
	 */
	protected $plugin_desc_short = '';

	/**
	 * @var string
	 */
	protected $plugin_desc_long = '';

	public function __construct() {
		$this->init();

		if ( empty( $this->plugin_name ) ) {
			throw new \Exception( 'SuggestionTrigger implementation must define a plugin.' );
		}
	}

	/**
	 * @return bool
	 */
	abstract public function check();

	abstract public function init();

	public function get_plugin_name() {
		return $this->plugin_name;
	}

	public function get_trigger_desc_short() {
		return $this->trigger_desc_short;
	}

	public function get_trigger_desc_long() {
		return $this->trigger_desc_long;
	}

	public function get_plugin_desc_short() {
		return $this->plugin_desc_short;
	}

	public function get_plugin_desc_long() {
		return $this->plugin_desc_long;
	}
}
