<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo\AbTest;

use InnoCraft\Experiments\Variations\VariationInterface;
use WpMatomo\Feature;

class FeatureVariation implements VariationInterface {

	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var int
	 */
	private $percentage;

	/**
	 * @var Feature
	 */
	private $feature;

	public function __construct( $name, $percentage, $feature ) {
		$this->name       = $name;
		$this->percentage = $percentage;
		$this->feature    = $feature;
	}

	public function getName() {
		return $this->name;
	}

	public function getPercentage() {
		return $this->percentage;
	}

	/**
	 * @return Feature
	 */
	public function getFeature() {
		return $this->feature;
	}

	public function run() {
		// empty
	}
}
