<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo\AbTest;

use InnoCraft\Experiments\Experiment;

class AbTest extends \WpMatomo\Feature {

	// TODO: prefix experiments library
	// TODO: make sure experiments library ends up in archive

	/**
	 * @var Experiment
	 */
	private $experiment;

	public function __construct( Experiment $experiment ) {
		$this->experiment = $experiment;
	}

	public function register_ajax() {
		$feature = $this->get_activated_feature();
		if ( empty( $feature ) ) {
			return;
		}

		$feature->register_ajax();
	}

	public function register_hooks() {
		$feature = $this->get_activated_feature();
		if ( empty( $feature ) ) {
			return;
		}

		$feature->register_hooks();
	}

	public function get_activated_feature() {
		$variation = $this->experiment->getActivatedVariation();
		if ( ! ( $variation instanceof FeatureVariation ) ) {
			return null;
		}
		return $variation->getFeature();
	}
}
