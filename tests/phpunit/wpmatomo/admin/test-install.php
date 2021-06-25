<?php
/**
 * @package matomo
 */

use WpMatomo\Roles;
use \WpMatomo\Settings;
use \WpMatomo\Admin\TrackingSettings;

class AdminInstallTest extends MatomoUnit_TestCase {
    /**
     * @var |WpMatomo
     */
    private $_wpMatomo;

    public function setUp() {
        parent::setUp();

        $this->_wpMatomo = new WpMatomo();

        wp_get_current_user()->add_role( Roles::ROLE_SUPERUSER );

        $this->assume_admin_page();
    }

    public function tearDown() {
        $this->reset_roles();
        parent::tearDown();
    }

    public function test_redirect_to_getting_started() {
        // load the options of the wpmatomo object. otherwise it's another instance and updating configuration will do nothing
    	$settings = $this->_wpMatomo::$settings;
        $original_show_get_started_page = $settings->get_global_option( Settings::SHOW_GET_STARTED_PAGE);
        $original_tracking_mode = $settings->get_global_option('track_mode');
	    $is_multi = isset($_GET['activate-multi']) ? $_GET['activate-multi'] : false;
	    unset($_GET['activate-multi']);
        // show starting page is disabled
        $settings->set_global_option(Settings::SHOW_GET_STARTED_PAGE, 0);
        $settings->save();
        $this->assertFalse($this->_wpMatomo->redirect_to_getting_started());
        // show starting page is enabled but track mode is disabled
        $settings->set_global_option(Settings::SHOW_GET_STARTED_PAGE, 1);
        $settings->set_global_option('track_mode', TrackingSettings::TRACK_MODE_DISABLED);
        $settings->save();
        $this->assertTrue($this->_wpMatomo->redirect_to_getting_started());
        // show getting started and track mode different of disabled
        $settings->set_global_option('track_mode', TrackingSettings::TRACK_MODE_DEFAULT);
        $settings->save();
        $this->assertFalse($this->_wpMatomo->redirect_to_getting_started());
	    $_GET['activate-multi'] = true;
	    $this->assertFalse($this->_wpMatomo->redirect_to_getting_started());
	    // restore initial configuration
        $settings->set_global_option('track_mode',  $original_tracking_mode);
        $settings->set_global_option(Settings::SHOW_GET_STARTED_PAGE,  $original_show_get_started_page);
        if ($is_multi !== false) {
        	$_GET['activate-multi'] = $is_multi;
        } else {
        	unset($_GET['activate-multi']);
        }
        $settings->save();
    }
}