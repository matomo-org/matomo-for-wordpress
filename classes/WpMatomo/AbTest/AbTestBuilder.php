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
use WpMatomo\Feature;

class AbTestBuilder {

	/**
	 * @var string
	 */
	private $experiment_name;

	/**
	 * @var FeatureVariation[]
	 */
	private $variations = [];

	/**
	 * @var AbTestStorage|null
	 */
	private $storage;

	public function __construct( $experiment_name, $ab_test_storage = null ) {
		$this->experiment_name = $experiment_name;
		$this->storage         = $ab_test_storage;
	}

	public static function experiment( $experiment_name ) {
		return new AbTestBuilder( $experiment_name );
	}

	public function variation( $variation_name, $variation_percent, Feature $feature ) {
		$this->variations[] = new FeatureVariation( $variation_name, $variation_percent, $feature );
		return $this;
	}

	public function build() {
		$experiment = new Experiment( $this->experiment_name, $this->variations, $this->get_experiment_config() );
		return new AbTest( $experiment );
	}

	public function get_experiment_config() {
		return [
			'storage' => $this->get_ab_test_storage(),
		];
	}

	private function get_ab_test_storage() {
		if ( ! empty( $this->storage ) ) {
			return $this->storage;
		}

		return AbTestStorage::get_global_instance();
	}
}
