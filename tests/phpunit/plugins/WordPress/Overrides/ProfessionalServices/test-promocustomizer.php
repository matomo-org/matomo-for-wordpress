<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

use Piwik\Plugins\WordPress\Overrides\ProfessionalServices\PromoCustomizer;

/**
 * @package matomo
 */
class PromoCustomizerTest extends MatomoUnit_TestCase {

	/**
	 * @var MatomoUnit_Matomo_Fixture
	 */
	private static $matomo_fixture;

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		self::$matomo_fixture = new MatomoUnit_Matomo_Fixture();
		self::$matomo_fixture->set_up();
	}

	public static function tearDownAfterClass(): void {
		self::$matomo_fixture->tear_down();

		parent::tearDownAfterClass();
	}

	public function test_customizePromoHtml() {
		$html = $this->get_test_promo_contents();

		$customizer = $this->makeTestCustomizer( true );
		$actual     = $customizer->customizePromoHtml( $html );

		$expected = <<<EOF
<div
    class="pluginPromo"
    vue-entry="CoreHome.ContentBlock"
    content-title="Unlock the Power of Custom Reports"
    image-url="plugins/ProfessionalServices/images/ad-customreports.png"
    image-alt-text="Preview image for Custom Reports"
>
    <div class="promo-content">
                    <div class="promo-features">
                <ul>
                                            <li><span class="promo-icon icon-ok"></span>Create analytics reports customised to your specific business goals and KPIs, ensuring they focus on the most relevant data.</li>
                                            <li><span class="promo-icon icon-ok"></span>Drill down into specific data for deeper insights into visitor behaviour and engagement.</li>
                                            <li><span class="promo-icon icon-ok"></span>Save time and resources by automating report generation, enabling real-time monitoring, and providing cost-effective analytics solutions without the need for third-party tools.</li>
                                    </ul>
            </div>
            </div>
    <div class="promo-actions">
        <a class="btn" href="https://plugins.matomo.org/CustomReports?add-to-cart=ws&amp;currency=EUR&amp;wp=1" target="_blank">
            Unlock
        </a>
        <a class="learn-more" href="https://plugins.matomo.org/CustomReports?currency=EUR&amp;wp=1" target="_blank" aria-label="Learn more about Custom Reports">
            Learn more
        </a>
    </div>
            <div class="promo-dismiss">
            If this feature is not relevant to your goals, you can <a href="#" vue-directive="ProfessionalServices.DismissPromoWidget" vue-directive-value="{&quot;widgetName&quot;:&quot;PromoCustomReports&quot;}">hide this section</a>.
        </div>
    </div>
EOF;

		$this->assertEquals( $expected, $actual );
	}

	public function test_customizePromoHtml_whenMarketplaceNotInstalled() {
		$html = $this->get_test_promo_contents();

		$customizer = $this->makeTestCustomizer( false );
		$actual     = $customizer->customizePromoHtml( $html );

		$expected = <<<EOF
<div
    class="pluginPromo"
    vue-entry="CoreHome.ContentBlock"
    content-title="Unlock the Power of Custom Reports"
    image-url="plugins/ProfessionalServices/images/ad-customreports.png"
    image-alt-text="Preview image for Custom Reports"
>
    <div class="promo-content">
                    <div class="promo-features">
                <ul>
                                            <li><span class="promo-icon icon-ok"></span>Create analytics reports customised to your specific business goals and KPIs, ensuring they focus on the most relevant data.</li>
                                            <li><span class="promo-icon icon-ok"></span>Drill down into specific data for deeper insights into visitor behaviour and engagement.</li>
                                            <li><span class="promo-icon icon-ok"></span>Save time and resources by automating report generation, enabling real-time monitoring, and providing cost-effective analytics solutions without the need for third-party tools.</li>
                                    </ul>
            </div>
            </div>
    <div class="promo-actions">
        <a class="btn" href="http://example.org/wp-admin/admin.php?page=matomo-marketplace&amp;tab=install">
            Unlock
        </a>
        <a class="learn-more" href="https://plugins.matomo.org/CustomReports?currency=EUR&amp;wp=1" target="_blank" aria-label="Learn more about Custom Reports">
            Learn more
        </a>
    </div>
            <div class="promo-dismiss">
            If this feature is not relevant to your goals, you can <a href="#" vue-directive="ProfessionalServices.DismissPromoWidget" vue-directive-value="{&quot;widgetName&quot;:&quot;PromoCustomReports&quot;}">hide this section</a>.
        </div>
    </div>
EOF;

		$this->assertEquals( $expected, $actual );
	}

	private function makeTestCustomizer( $marketplace_installed ) {
		return new class( $marketplace_installed ) extends PromoCustomizer {
			private $marketplace_installed;

			public function __construct( $marketplace_installed ) {
				$this->marketplace_installed = $marketplace_installed;
			}

			protected function isMwpMarketplaceInstalled() {
				return $this->marketplace_installed;
			}
		};
	}

	private function get_test_promo_contents() {
		return <<<EOF
<div
    class="pluginPromo"
    vue-entry="CoreHome.ContentBlock"
    content-title="Unlock the Power of Custom Reports"
    image-url="plugins/ProfessionalServices/images/ad-customreports.png"
    image-alt-text="Preview image for Custom Reports"
>
    <div class="promo-content">
                    <div class="promo-features">
                <ul>
                                            <li><span class="promo-icon icon-ok"></span>Create analytics reports customised to your specific business goals and KPIs, ensuring they focus on the most relevant data.</li>
                                            <li><span class="promo-icon icon-ok"></span>Drill down into specific data for deeper insights into visitor behaviour and engagement.</li>
                                            <li><span class="promo-icon icon-ok"></span>Save time and resources by automating report generation, enabling real-time monitoring, and providing cost-effective analytics solutions without the need for third-party tools.</li>
                                    </ul>
            </div>
            </div>
    <div class="promo-actions">
        <a class="btn" href="?module=Marketplace&action=overview#?showPlugin=CustomReports">
            Start free trial
        </a>
        <a class="learn-more" href="?module=Marketplace&action=overview#?showPlugin=CustomReports" aria-label="Learn more about Custom Reports">
            Learn more
        </a>
    </div>
            <div class="promo-dismiss">
            If this feature is not relevant to your goals, you can <a href="#" vue-directive="ProfessionalServices.DismissPromoWidget" vue-directive-value="{&quot;widgetName&quot;:&quot;PromoCustomReports&quot;}">hide this section</a>.
        </div>
    </div>
EOF;
	}
}
