<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

use Piwik\Config;
use Piwik\Container\StaticContainer;
use Piwik\Plugin\Manager;
use Piwik\Plugins\ProfessionalServices\PromoWidgetDismissal;
use Piwik\Plugins\WordPress\Overrides\ProfessionalServices\PromoWidgetApplicable;

/**
 * @package matomo
 * phpcs:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
 */
class PromoWidgetApplicableTest extends MatomoUnit_TestCase {

	/**
	 * @var MatomoUnit_Matomo_Fixture
	 */
	private static $matomo_fixture;

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		self::$matomo_fixture = new MatomoUnit_Matomo_Fixture();
		self::$matomo_fixture->set_up();
		self::$matomo_fixture->create_set_super_admin( self::factory() );
	}

	public static function tearDownAfterClass(): void {
		self::$matomo_fixture->tear_down();

		parent::tearDownAfterClass();
	}

	public function test_check_whenPluginPromoIsNotAllowed() {
		$applicable = $this->makePromoWidgetApplicable( false );
		$actual     = $applicable->check( 'AbTesting', 'PromoAbTesting' );
		$this->assertFalse( $actual );
	}

	public function test_check_whenPluginPromoIsDismissed() {
		StaticContainer::get( PromoWidgetDismissal::class )->dismissPromoWidget( 'PromoFunnels' );

		$applicable = $this->makePromoWidgetApplicable( false );
		$actual     = $applicable->check( 'Funnels', 'PromoFunnels' );
		$this->assertFalse( $actual );
	}

	public function test_check_whenPluginIsAlreadyActivated() {
		$applicable = $this->makePromoWidgetApplicable( true );
		$actual     = $applicable->check( 'Funnels', 'PromoFunnels' );
		$this->assertFalse( $actual );
	}

	public function test_check_whenPluginPromoShouldBeDisplayed() {
		$applicable = $this->makePromoWidgetApplicable( false );
		$actual     = $applicable->check( 'Funnels', 'PromoFunnels' );
		$this->assertTrue( $actual );
	}

	private function makePromoWidgetApplicable( $is_plugin_activated ) {
		return new class(
			$is_plugin_activated,
			Manager::getInstance(),
			Config::getInstance(),
			StaticContainer::get( PromoWidgetDismissal::class )
		) extends PromoWidgetApplicable {

			private $is_plugin_activated;

			public function __construct(
				$is_plugin_activated,
				Manager $manager,
				Config $config,
				PromoWidgetDismissal $promo_widget_dismissal
			) {
				parent::__construct( $manager, $config, $promo_widget_dismissal );
				$this->is_plugin_activated = $is_plugin_activated;
			}

			protected function isMatomoPluginActivated( $pluginName ) {
				return $this->is_plugin_activated;
			}
		};
	}
}
