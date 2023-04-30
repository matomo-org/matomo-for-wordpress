<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\WordPress;

use Exception;
use Piwik\API\Request;
use Piwik\Common;
use Piwik\FrontController;
use Piwik\Piwik;
use Piwik\Plugin;
use Piwik\Plugin\Manager;
use Piwik\Plugins\CoreHome\SystemSummary\Item;
use Piwik\Scheduler\Task;
use Piwik\Url;
use Piwik\Widget\WidgetsList;
use WpMatomo\Bootstrap;

if (!defined( 'ABSPATH')) {
    exit; // if accessed directly
}

class WordPress extends Plugin
{
	public static $is_archiving = false;

    /**
     * @see \Piwik\Plugin::registerEvents
     */
    public function registerEvents()
    {
        return array(
            'API.UsersManager.createAppSpecificTokenAuth' => 'disableApiIfNotBootstrapped',
            'Request.dispatch' => 'onDispatchRequest',
            'Request.dispatch.end' => 'onDispatchRequestEnd',
            'User.isNotAuthorized' => array('before' => true, 'function' => 'noAccess'),
            'Http.sendHttpRequest' => 'onSendHttpRequestBy',
            'Widget.filterWidgets' => 'filterWidgets',
            'System.filterSystemSummaryItems' => 'filterSystemSummaryItems',
            'CliMulti.supportsAsync' => 'supportsAsync',
            'Template.header' => 'onHeader',
            'AssetManager.makeNewAssetManagerObject' => 'makeNewAssetManagerObject',
            'ScheduledTasks.shouldExecuteTask' => 'shouldExecuteTask',
            'API.TagManager.getContainerInstallInstructions.end' => 'addInstallInstructions',
            'API.Tour.getChallenges.end' => 'modifyTourChallenges',
	        'API.ScheduledReports.generateReport.end' => 'onGenerateReportEnd',
            'Translate.getClientSideTranslationKeys' => 'getClientSideTranslationKeys',
            'CustomJsTracker.manipulateJsTracker' => 'updateHeatmapTrackerPath',
            'Visualization.beforeRender' => 'onBeforeRenderView',
            'AssetManager.getStylesheetFiles'  => 'getStylesheetFiles',
            'Controller.PrivacyManager.usersOptOut.end' => 'onUserOptOutRender',
        );
    }

	public function onUserOptOutRender(&$result)
	{
		$result = preg_replace('/<div [a-z-]+="PrivacyManager.OptOutCustomizer".*?>/s', '<div class="WordPressOptOutCustomizer">
    <p>
        Use the short code <code>[matomo_opt_out]</code> to embed the opt out into your website.<br>
        You can use these short code options:</p>
    <ul style="margin:20px;">
        <li style="list-style: disc">language - eg de or en. By default the language is detected automatically based on the user\'s browser</li>
    </ul>
    <p>Example: <code>[matomo_opt_out language=de]</code></p>', $result);
	}

    public function onBeforeRenderView (Plugin\ViewDataTable $view)
    {
    	if ($view->requestConfig->getApiModuleToRequest() === 'UserCountry' && $view->config->show_footer_message && strpos($view->config->show_footer_message, 'href') !== false) {
    		// dont suggest setting up geoip
    		$view->config->show_footer_message = '';
	    }
    }

    public function updateHeatmapTrackerPath(&$content)
    {
	    $webRootDirs = Manager::getInstance()->getWebRootDirectoriesForCustomPluginDirs();
	    if (!empty($webRootDirs['HeatmapSessionRecording'])) {
		    $baseUrl = trim($webRootDirs['HeatmapSessionRecording'], '/') . '/HeatmapSessionRecording/configs.php';
    	    $content = str_replace('plugins/HeatmapSessionRecording/configs.php', $baseUrl, $content);
	    }
    }

	public function getClientSideTranslationKeys(&$translationKeys)
	{
		$translationKeys[] = 'Feedback_SearchOnMatomo';
	}

    public function modifyTourChallenges(&$challenges)
    {
    	foreach ($challenges as $index => $challenge) {
    		if ($challenge['id'] === 'track_data') {
    			$challenges[$index]['url'] = ''; // we can't generate menu url for tracking settings since we're not showing the menu
    			$challenges[$index]['description'] = __('In WordPress Admin go to Matomo Analytics => Settings => Tracking to embed the tracking code.', 'matomo');
		    } elseif ($challenge['id'] === 'custom_logo') {
				unset($challenges[$index]);
		    }
	    }
    }

    public function addInstallInstructions(&$instructions)
    {
	    $instructions[] = array(
		    'description' => __('Alternatively, simply go to "Tracking Settings" in your WordPress Admin and select "Tag Manager" as tracking mode or choose "Manually" and paste the above code into the tracking code field.', 'matomo'),
		    'embedCode' => '',
		    'helpUrl' => ''
	    );
    }

	/**
	 * @param $shouldExecuteTask
	 * @param Task $task
	 */
	public function shouldExecuteTask(&$shouldExecuteTask, $task)
	{
		if ($shouldExecuteTask && $task) {
			$blockedMethods = array(
				'updateSpammerBlacklist',
				'updateSpammerList',
				'updateSearchEngines',
				'updateSocials',
				'Piwik\Plugins\Referrers\Tasks.update', // just in case any of the tasks was renamed...
				'Piwik\Plugins\CoreAdminHome\Tasks.update' // just in case any of the tasks was renamed...
			);

			// don't ping github... we update these through new releases
			if (in_array($task->getMethodName(), $blockedMethods)) {
				$shouldExecuteTask = false;
				return;
			}

			$taskName = $task->getName();

			foreach ($blockedMethods as $blockedMethod) {
				if (stripos($taskName, $blockedMethod) !== false) {
					// just in case for some reason wasn't matched above
					$shouldExecuteTask = false;
					return;
				}
			}
		}
	}

    public function makeNewAssetManagerObject(&$assetManager)
    {
	    $assetManager = new WpAssetManager();
    }

    public function supportsAsync(&$supportsAsync)
    {
        if (is_multisite()
            || (defined('WP_DEBUG') && WP_DEBUG)
            || !empty($_SERVER['MATOMO_WP_ROOT_PATH'])
            || !matomo_has_compatible_content_dir()
	        || (defined( 'MATOMO_SUPPORT_ASYNC_ARCHIVING') && !MATOMO_SUPPORT_ASYNC_ARCHIVING)
        ) {
            // console wouldn't really work in multi site mode... therefore we prefer to archive in the same request
            // WP_DEBUG also breaks things since it's logging things to stdout and then safe unserialise doesn't work
            // disabling it also when server environment variable is set as it's likely only set in web requests through web server
            // but not on the CLI
            $supportsAsync = false;
        }
    }

    public function onHeader(&$out)
    {
        $out .= '<style type="text/css">#header_message {display:none !important; }</style>';
    }

    /**
     * @param Item[] $systemSummaryItems
     */
    public function filterSystemSummaryItems(&$systemSummaryItems)
    {
        $blockedItems = array(
            'php-version', 'mysql-version', 'plugins', 'trackingfailures',
            'websites', 'users', 'piwik-version', 'matomo-version'
        );

        foreach ($systemSummaryItems as $index => $item) {
            if ($item && in_array($item->getKey(), $blockedItems, true)) {
                $systemSummaryItems[$index] = null;
            }
        }
    }

    public function filterWidgets(WidgetsList $list)
    {
        // it's fine if they are being viewed we just don't want to show them in the admin so much to keep things simple
        $list->remove('About Matomo', 'Installation_SystemCheck');
        $list->remove('About Matomo', 'CoreAdminHome_TrackingFailures');
        $list->remove('About Matomo', 'CoreHome_SystemSummaryWidget');
        $list->remove('About Matomo', 'CoreHome_QuickLinks');
    }

    public function isTrackerPlugin() {
        return true;
    }

    public function onSendHttpRequestBy($url, $params, &$response, &$status, &$headers)
    {
    	if (strpos($url, 'https://raw.github') === 0) {
    		// we don't want to ping github from wordpress since we're not disclosing connections to github in readme
		    // and/or UI. We could eventually allow this by adding an option into the UI and notifying users there
		    // that we send requests to github... We would then also allow the tasks in `shouldExecuteTask` method.
    		$status = 403;
		    $response = '';
    		return;
	    }

        if ((strpos($url, 'module=API&method=API.get') !== false
             && strpos($url, '&trigger=archivephp') !== false
             && Url::isValidHost(parse_url($url, PHP_URL_HOST)))
            ||
            (strpos($url, 'module=API&method=CoreAdminHome.archiveReports') !== false
             && strpos($url, '&trigger=archivephp') !== false
             && Url::isValidHost(parse_url($url, PHP_URL_HOST)))
        ) {
            // archiving query... we avoid issueing an http request for many reasons...
            // eg user might be using self signed certificate and request fails
            // eg http requests may not be allowed
            // eg because the WP user wouldn't be logged in the auth wouldn't work
            // etc.

            \Piwik\Access::doAsSuperUser(function () use ($url, &$response) {
            	WordPress::$is_archiving = true;
            	// refs #118 because there is no actual user when archiving there is also no token etc
                $urlQuery = parse_url($url, PHP_URL_QUERY);
                $request = new Request($urlQuery, array('serialize' => 1));
                $response = $request->process();
	            WordPress::$is_archiving = false;
            });
            $status = 200;
            return;
        }

        $headers = $params['headers'];
        $rawHeaders = array();
        foreach ($headers as $header) {
            if (!empty($header) && strpos($header, ':') !== false) {
                $parts = explode(':', $header);
                $rawHeaders[trim($parts[0])] = trim($parts[1]);
            }
        }
        $args = array(
            'method'              => $params['httpMethod'],
            'timeout'             => $params['timeout'],
            'headers'             => $rawHeaders,
            'body'                => $params['body'],
            'sslverify'           => true,
        );
        if (!empty($params['userAgent'])) {
            $args['user-agent'] = $params['userAgent'];
        }
        if (!empty($params['destinationPath'])) {
            $args['filename'] = $params['destinationPath'];
            $args['stream'] = true;
        }
	    if (isset($params['verifySsl']) && !$params['verifySsl']) {
		    // by default we want to reuse WP default value unless someone specifically disabled it for Matomo
		    $args['sslverify'] = false;
	    }
        $wpResponse = wp_remote_request($url, $args);

        if (is_object($wpResponse) && is_wp_error($wpResponse)) {
            throw new Exception("Error while fetching data: " . $wpResponse->get_error_message());
        }

        $status = wp_remote_retrieve_response_code($wpResponse);
        $headers = wp_remote_retrieve_headers($wpResponse);
        $response = wp_remote_retrieve_body($wpResponse);
    }

    public function onGenerateReportEnd()
    {
    	if (Request::isCurrentApiRequestTheRootApiRequest() && !headers_sent()) {
    		// fix https://github.com/matomo-org/wp-matomo/issues/98
		    // When some plugin does an ob_start before the API is being executed then the following happens:
		    // * PDF is generated and sent
		    // * We send the application/pdf content-type header
		    // * The api call finishes
		    // * The response builder sends text/xml header and because the headers haven't been send yet, this actually overwrites the application/pdf header
		    // * An XML error is shown in the UI.
		    // The workaround is basically to make sure to flush the API call between API call finished and the response builder trying to send the text/xml header
		    // It seems mostly an issue for the Scheduled Reports renderer as this is basically using an API call, it is basically using the XML renderer, but sending a different content type
		    if (ob_get_length()) {
			    ob_end_flush();
		    }
	    }
    }

    public function onDispatchRequestEnd(&$result, $module, $action, $parameters) {
    	if (!empty($result) && is_string($result)) {
    		// https://wordpress.org/support/topic/bugged-favicon/#post-12995669
    		$result = str_replace('<link rel="mask-icon"', '<link rel="ignore-mask-icon-ignore"', $result);
    		$result = str_replace('plugins/CoreHome/images/applePinnedTab.svg', '', $result);
	    }
    }
    public function onDispatchRequest(&$module, &$action, &$parameters)
    {
        if ($module === 'Proxy' && in_array($action, array('getNonCoreJs', 'getCoreJs', 'getCss'))) {
            remove_action( 'shutdown', 'wp_ob_end_flush_all', 1 );
        } else {
	        if (function_exists('ini_get') && @ini_get('zlib.output_compression') === '1') {
		        remove_action( 'shutdown', 'wp_ob_end_flush_all', 1 );
	        }
        }

	    $requestedModule = !empty($module) ? Common::mb_strtolower($module) : '';
	    $requestedAction = !empty($action) ? Common::mb_strtolower($action) : '';

	    if (!WordPress::$is_archiving
	        && !Common::isPhpCliMode()
	        && $requestedModule === 'api'
	        && (empty($requestedAction) || $requestedAction === 'index')) {
		    $tokenRequest = Common::getRequestVar('token_auth', false, 'string');
		    $tokenUser = Piwik::getCurrentUserTokenAuth();

		    if (!$tokenRequest || $tokenRequest !== $tokenUser) {
			    throw new Exception(Piwik::translate('General_ExceptionInvalidToken'));
		    }
	    }

        if ($requestedModule === 'login') {
            if ($action === 'ajaxNoAccess' || $action === 'bruteForceLog') {
                return; // allowed
            }
	        throw new Exception( 'This feature '.$requestedModule. ' / ' .$requestedAction .' is not available' );
        }

        if (($requestedModule === 'corepluginsadmin' && $action !== 'safemode')
            || $requestedModule === 'installation'
            || $requestedModule === 'coreupdater') {
	        throw new Exception( 'This feature '.$requestedModule. ' / ' .$requestedAction .' is not available' );
        }

        $blockedActions = array(
            array('coreadminhome', 'trackingcodegenerator'),
            array('usersmanager', 'index'),
            array('usersmanager', ''),
            array('usersmanager', 'addnewtoken'),
            array('usersmanager', 'deletetoken'),
            array('usersmanager', 'usersecurity'),
	        array('sitesmanager', ''),
            array('sitesmanager', 'globalsettings'),
            array('feedback', ''),
            array('feedback', 'index'),
            array('diagnostics', 'configfile'),
            array('api', 'listallapi'),
        );

        foreach ($blockedActions as $blockedAction) {
            if ($requestedModule === $blockedAction[0] && $requestedAction == $blockedAction[1]) {
	            throw new Exception( 'This feature '.$requestedModule. ' / ' .$requestedAction .' is not available' );
            }
        }
        
        if ($requestedModule === 'sitesmanager' && $requestedAction === 'sitewithoutdata') {
            // we don't want the no data message to appear as it contains integration instructions which aren't needed
            // and links to not existing sites
            $module = 'CoreHome';
            $action = 'index';
        }
    }

    public static function getWpLoginUrl()
    {
    	$forceFrontPage = defined('MATOMO_LOGIN_REDIRECT') && MATOMO_LOGIN_REDIRECT === 'frontpage';
    	$forceLoginUrl = defined('MATOMO_LOGIN_REDIRECT') && MATOMO_LOGIN_REDIRECT === 'login';

	    if (!$forceLoginUrl &&
	        ($forceFrontPage
	            || is_plugin_active('wps-hide-login/wps-hide-login.php'))) {
		    $redirect_url = home_url();
	    } else {
		    $redirect_url = wp_login_url(\WpMatomo\Admin\Menu::get_reporting_url());
	    }

	    return $redirect_url;
    }

    public function noAccess(Exception $exception)
    {
        if (Common::isXmlHttpRequest()) {
            $frontController = FrontController::getInstance();
            echo $frontController->dispatch('Login', 'ajaxNoAccess', array($exception->getMessage()));
            return;
        }

	    $redirect_url = WordPress::getWpLoginUrl();
	    wp_safe_redirect($redirect_url);
        exit;
    }

    public function disableApiIfNotBootstrapped()
    {
        if (!Bootstrap::is_bootstrapped()) {
            throw new \Exception('This feature is not available');
        }
    }

    public function throwNotAvailableException()
    {
        throw new \Exception('This feature is not available');
    }

	public function getStylesheetFiles(&$files)
	{
		$files[] = "../plugins/WordPress/stylesheets/user.css";
		$files[] = "../plugins/WordPress/stylesheets/optout.css";
		$files[] = "../plugins/WordPress/stylesheets/export.css";
	}

}
