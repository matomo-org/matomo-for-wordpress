<?php
/**
 * @package matomo
 */

use WpMatomo\Admin\Dashboard;
use WpMatomo\Report\Dates;
use WpMatomo\Report\Renderer;

class AdminDashboardTest extends MatomoAnalytics_TestCase {

	const EXAMPLE_ID = 'VisitsSummary_get';

	/**
	 * @var Dashboard
	 */
	private $dashboard;

	public function setUp() {
		parent::setUp();

		$this->dashboard = new Dashboard();

		$this->assume_admin_page();
		$this->create_set_super_admin();
	}

	public function tearDown() {
		$_REQUEST = array();
		$_POST    = array();
		parent::tearDown();
	}

	public function test_get_widgets_is_empty_by_default() {
		$this->assertSame( array(), $this->dashboard->get_widgets() );
	}

	public function test_get_widgets_toggle_widget() {
		$this->dashboard->toggle_widget( self::EXAMPLE_ID, Dates::TODAY );

		$this->assertSame(
			array(
				array(
					'unique_id' => 'VisitsSummary_get',
					'date'      => 'today',
				),
			),
			$this->dashboard->get_widgets()
		);

		$this->dashboard->toggle_widget( self::EXAMPLE_ID, Dates::YESTERDAY );
		$this->dashboard->toggle_widget( 'foobar', Dates::YESTERDAY );

		$this->assertSame(
			array(
				array(
					'unique_id' => 'VisitsSummary_get',
					'date'      => 'today',
				),
				array(
					'unique_id' => 'VisitsSummary_get',
					'date'      => 'yesterday',
				),
				array(
					'unique_id' => 'foobar',
					'date'      => 'yesterday',
				),
			),
			$this->dashboard->get_widgets()
		);
	}

	public function test_toggle_widget_removes_widgt_if_already_exists() {
		$this->dashboard->toggle_widget( self::EXAMPLE_ID, Dates::TODAY );
		$this->dashboard->toggle_widget( self::EXAMPLE_ID, Dates::YESTERDAY );
		$this->dashboard->toggle_widget( 'foobar', Dates::YESTERDAY );

		$this->assertCount( 3, $this->dashboard->get_widgets() );
		$this->assertTrue( $this->dashboard->has_widget( self::EXAMPLE_ID, Dates::YESTERDAY ) );

		$this->dashboard->toggle_widget( self::EXAMPLE_ID, Dates::YESTERDAY );

		$this->assertFalse( $this->dashboard->has_widget( self::EXAMPLE_ID, Dates::YESTERDAY ) );
		$this->assertSame(
			array(
				array(
					'unique_id' => 'VisitsSummary_get',
					'date'      => 'today',
				),
				array(
					'unique_id' => 'foobar',
					'date'      => 'yesterday',
				),
			),
			$this->dashboard->get_widgets()
		);
	}

	public function test_add_dashboard_widgets_when_no_widgets_defined() {
		global $wp_meta_boxes;
		$this->dashboard->add_dashboard_widgets();
		$this->assertEmpty( $wp_meta_boxes );
	}

	public function test_add_dashboard_widgets_only_adds_valid_widgets() {
		if ( ! function_exists( 'wp_add_dashboard_widget' ) ) {
			include_once ABSPATH . '/wp-admin/includes/dashboard.php';
		}
		global $wp_meta_boxes;

		$this->dashboard->toggle_widget( self::EXAMPLE_ID, Dates::TODAY );
		$this->dashboard->toggle_widget( self::EXAMPLE_ID, Dates::YESTERDAY );
		$this->dashboard->toggle_widget( 'foobar', Dates::YESTERDAY );

		$this->dashboard->add_dashboard_widgets();

		$this->assertCount( 2, $wp_meta_boxes['edit-post']['normal']['core'] );
		$this->assertTrue( isset( $wp_meta_boxes['edit-post']['normal']['core']['matomo_dashboard_widget_VisitsSummary_get_today'] ) );
		$this->assertTrue( isset( $wp_meta_boxes['edit-post']['normal']['core']['matomo_dashboard_widget_VisitsSummary_get_yesterday'] ) );
	}

	public function test_has_widget() {
		$this->assertFalse( $this->dashboard->has_widget( 'foo', 'bar' ) );
		$this->assertFalse( $this->dashboard->has_widget( self::EXAMPLE_ID, Dates::TODAY ) );

		$this->dashboard->toggle_widget( self::EXAMPLE_ID, Dates::TODAY );

		$this->assertFalse( $this->dashboard->has_widget( 'foo', 'bar' ) );
		$this->assertTrue( $this->dashboard->has_widget( self::EXAMPLE_ID, Dates::TODAY ) );
		$this->assertFalse( $this->dashboard->has_widget( self::EXAMPLE_ID, Dates::YESTERDAY ) );
		$this->assertFalse( $this->dashboard->has_widget( self::EXAMPLE_ID, Dates::THIS_MONTH ) );

		$this->dashboard->toggle_widget( self::EXAMPLE_ID, Dates::YESTERDAY );

		$this->assertTrue( $this->dashboard->has_widget( self::EXAMPLE_ID, Dates::TODAY ) );
		$this->assertTrue( $this->dashboard->has_widget( self::EXAMPLE_ID, Dates::YESTERDAY ) );
		$this->assertFalse( $this->dashboard->has_widget( self::EXAMPLE_ID, Dates::THIS_MONTH ) );
	}

	public function test_uninstall() {
		$this->dashboard->toggle_widget( self::EXAMPLE_ID, Dates::TODAY );

		$this->assertNotEmpty( $this->dashboard->get_widgets() );

		$this->dashboard->uninstall();

		$this->assertSame( array(), $this->dashboard->get_widgets() );
	}

	/**
	 * @dataProvider getValidWidgetProvider
	 */
	public function test_is_valid_widget( $expected, $unique_id, $date ) {
		$widget = $this->dashboard->is_valid_widget( $unique_id, $date );
		$this->assertSame( $expected, ! empty( $widget ) );
		if ( $expected ) {
			$this->assertSame( $widget['report']['uniqueId'], $unique_id );
			$this->assertNotEmpty( $widget['date'] );
		}
	}

	public function getValidWidgetProvider() {
		return array(
			array( true, Renderer::CUSTOM_UNIQUE_ID_VISITS_OVER_TIME, Dates::TODAY ),
			array( true, Renderer::CUSTOM_UNIQUE_ID_VISITS_OVER_TIME, Dates::LAST_WEEK ),
			array( false, Renderer::CUSTOM_UNIQUE_ID_VISITS_OVER_TIME, 'foobar' ),

			array( true, self::EXAMPLE_ID, Dates::TODAY ),
			array( true, 'DevicesDetection_getBrowsers', Dates::LAST_WEEK ),
			array( false, 'foobar_baz', Dates::LAST_WEEK ),
		);
	}

}
