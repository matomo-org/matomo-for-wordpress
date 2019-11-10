<?php
$GLOBALS['CONFIG_INI_PATH_RESOLVER'] = function () {
	if ( defined( 'ABSPATH' )
	     && defined( 'MATOMO_CONFIG_PATH' ) ) {
		$paths = new \WpMatomo\Paths();

		return $paths->get_config_ini_path();
	}
};
if ( ! defined( 'PIWIK_ENABLE_ERROR_HANDLER' ) ) {
	// we prefer using WP error handler
	define( 'PIWIK_ENABLE_ERROR_HANDLER', false );
}

$was_loaded_directly = ! defined( 'ABSPATH' );

if ( $was_loaded_directly ) {
	// prevent from loading twice
	require_once( dirname( __FILE__ ) . '/../../../../wp-load.php' );
}

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

if ( !is_plugin_active('matomo/matomo.php')
     && !defined( 'MATOMO_PHPUNIT_TEST' )
     && !MATOMO_PHPUNIT_TEST ) { // during tests the plugin may temporarily not be active
	exit;
}

if ($was_loaded_directly) {
	// do not strip slashes if we bootstrap matomo within a regular wordpress request
	if (!empty($_GET)) {
		$_GET     = stripslashes_deep( $_GET );
	}
	if (!empty($_POST)) {
		$_POST    = stripslashes_deep( $_POST );
	}
	if (!empty($_COOKIE)) {
		$_COOKIE  = stripslashes_deep( $_COOKIE );
	}
	if (!empty($_SERVER)) {
		$_SERVER  = stripslashes_deep( $_SERVER );
	}
	if (!empty($_REQUEST)) {
		$_REQUEST = stripslashes_deep( $_REQUEST );
	}
}


if ( is_matomo_app_request() ) {
	// pretend we are in the admin... potentially avoiding caching etc
	$GLOBALS['hook_suffix'] = '';
	include_once ABSPATH . '/wp-admin/includes/class-wp-screen.php';
	$GLOBALS['current_screen'] = WP_Screen::get();

	// we disable jsonp
	unset($_GET['jsoncallback']);
	unset($_GET['callback']);
	unset($_POST['jsoncallback']);
	unset($_POST['callback']);
}

if ( ! defined( 'PIWIK_USER_PATH' ) ) {
	define( 'PIWIK_USER_PATH', dirname( MATOMO_ANALYTICS_FILE ) );
}

$GLOBALS['MATOMO_MODIFY_CONFIG_SETTINGS'] = function ($settings) {
	$plugins = $settings['Plugins'];
	if (is_array($settings['Plugins'])) {
		$pluginsToRemove = array('Marketplace', 'MultiSites', 'TwoFactorAuth', 'Widgetize', 'Monolog', 'Feedback', 'ExamplePlugin', 'ExampleAPI', 'ProfessionalServices', 'MobileAppMeasurable');
		foreach ($pluginsToRemove as $pluginToRemove) {
			// Marketplace => this is instead done in wordpress
			// MultiSites => doesn't really make sense since we have only one website per installation
			// TwoFactorAuth => not needed as login is being handled by WordPress
			// widgetize for now we don't want to allow widgetizing as it is based on the token_auth authentication
			// Monolog => we use our own logger
			// ProfessionalServices => we advertise in the WP plugin itself instead
			// feedback => we want to hide things like Need help in the admin etc
			// MobileAppMeasurable => for WP mobile apps are not a thing
			// custom variables we don't want to enable as we will deprecate them in Matomo 4 anyway => used to be disabled but we need to make sure the columns get installed otherwise matomo has issues... need to wait to matomo 4 to remove it
			$pos = array_search($pluginToRemove, $plugins['Plugins']);
			if ($pos !== false) {
				array_splice($plugins['Plugins'], $pos, 1);
			}
		}
		if (has_matomo_tag_manager()) {
			$plugins['Plugins'][] = 'TagManager';
		}
	}
	if (!empty($GLOBALS['MATOMO_PLUGINS_ENABLED'])) {
		foreach ($GLOBALS['MATOMO_PLUGINS_ENABLED'] as $plugin) {
			if (!in_array($plugin, $plugins['Plugins'])) {
				$plugins['Plugins'][] = $plugin;
			}
		}
	}
	$settings['Plugins'] = $plugins;
	return $settings;
};
