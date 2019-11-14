<?php

use Piwik\Config;
use Piwik\Plugin\API;
use Piwik\Tracker;
use Piwik\Tracker\Cache;

$GLOBALS['PIWIK_TRACKER_DEBUG'] = false;
require_once __DIR__ . '/../../../app/libs/PiwikTracker/PiwikTracker.php';

/**
 * Tracker that uses core/Tracker.php directly.
 */
class MatomoLocalTracker extends PiwikTracker {

	protected function getBaseUrl() {
		return self::$URL; // avoid adding another query string
	}

	protected function sendRequest( $url, $method = 'GET', $data = null, $force = false ) {
		if ( $this->DEBUG_APPEND_URL ) {
			$url .= $this->DEBUG_APPEND_URL;
		}
		// if doing a bulk request, store the url
		if ( $this->doBulkRequests && ! $force ) {
			$this->storedTrackingActions[] = $url;

			return true;
		}
		if ( 'POST' === $method ) {
			$requests = array();
			foreach ( $this->storedTrackingActions as $action ) {
				$requests[] = $this->parseUrl( $action );
			}
			$test_environment_args = array();
		} else {
			$test_environment_args = $this->parseUrl( $url );
			$requests              = array( $test_environment_args );
		}
		// unset cached values
		Cache::$cache = null;
		\Piwik\Cache::flushAll();
		Tracker\Visit::$dimensions = null;
		// save some values
		$plugins            = Config::getInstance()->Plugins['Plugins'];
		$old_tracker_config = Config::getInstance()->Tracker;

		\Piwik\Plugin\Manager::getInstance()->unloadPlugins();
		// modify config
		\Piwik\SettingsServer::setIsTrackerApiRequest();
		$GLOBALS['PIWIK_TRACKER_LOCAL_TRACKING'] = true;
		Tracker::$initTrackerMode                = false;
		Tracker::setTestEnvironment( $test_environment_args, $method );
		// set language
		$old_lang                        = isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '';
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = $this->acceptLanguage;
		// set user agent
		$old_user_agent             = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '';
		$_SERVER['HTTP_USER_AGENT'] = $this->userAgent;
		// set cookie
		$old_cookie = $_COOKIE;
		// parse_str(parse_url($this->requestCookie, PHP_URL_QUERY), $_COOKIE);
		// do tracking and capture output
		ob_start();
		$local_tracker = new Tracker();
		$request       = new Tracker\RequestSet();
		$request->setRequests( $requests );

		\Piwik\Plugin\Manager::getInstance()->loadTrackerPlugins();

		$handler  = Tracker\Handler\Factory::make();
		$response = $local_tracker->main( $handler, $request );
		if ( ! is_null( $response ) ) {
			echo $response;
		}
		$output = ob_get_contents();
		ob_end_clean();
		// restore vars
		Config::getInstance()->Tracker           = $old_tracker_config;
		$_SERVER['HTTP_ACCEPT_LANGUAGE']         = $old_lang;
		$_SERVER['HTTP_USER_AGENT']              = $old_user_agent;
		$_COOKIE                                 = $old_cookie;
		$GLOBALS['PIWIK_TRACKER_LOCAL_TRACKING'] = false;
		\Piwik\SettingsServer::setIsNotTrackerApiRequest();
		unset( $_GET['bots'] );
		// reload plugins
		\Piwik\Plugin\Manager::getInstance()->loadPlugins( $plugins );
		\Piwik\Singleton::clearAll();
		API::unsetAllInstances();
		\Piwik\Cache::flushAll();

		return $output;
	}

	private function parseUrl( $url ) {
		// parse url
		$query = parse_url( $url, PHP_URL_QUERY );
		if ( false === $query ) {
			return;
		}
		parse_str( $query, $args );
		// make sure bots is set if needed
		if ( isset( $args['bots'] ) ) {
			$_GET['bots'] = true;
		}

		return $args;
	}
}
