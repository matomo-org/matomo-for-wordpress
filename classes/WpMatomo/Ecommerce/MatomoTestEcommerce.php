<?php

namespace WpMatomo\Ecommerce;

/**
 * this class is required only for phpunit tests.
 * It allow to change the visibility of some methods of the Base class
 * and so allow to test them in the unit tests
 *
 * phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod.Found
 */
class MatomoTestEcommerce extends Base {

	/**
	 * Render public the wrap_script method. Required for the unit tests
	 *
	 * @param string $script
	 *
	 * @return string
	 * @see Base::wrap_script()
	 */
	public function wrap_script( $script ) {
		return parent::wrap_script( $script );
	}

	/**
	 * Render public the wrap_script method. Required for the unit tests
	 *
	 * @param [] $params
	 *
	 * @return string
	 * @see Base::make_matomo_js_tracker_call()
	 */
	public function make_matomo_js_tracker_call( $params ) {
		return parent::make_matomo_js_tracker_call( $params );
	}
}
