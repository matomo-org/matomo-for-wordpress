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
use Piwik\AuthResult;
use Piwik\Common;
use Piwik\Container\StaticContainer;
use Piwik\FrontController;
use Piwik\Piwik;
use Piwik\Plugin;
use Piwik\Plugins\CoreHome\SystemSummary\Item;
use Piwik\Scheduler\Task;
use Piwik\Url;
use Piwik\View;
use Piwik\Widget\WidgetsList;
use WpMatomo\Access;
use WpMatomo\Admin\TrackingSettings;
use WpMatomo\API;
use WpMatomo\Bootstrap;
use WpMatomo\Settings;
use WpMatomo\Site;
use WpMatomo\User;

if (!defined( 'ABSPATH')) {
    exit; // if accessed directly
}

class WordPress extends Plugin
{

    /**
     * @see \Piwik\Plugin::registerEvents
     */
    public function registerEvents()
    {
        return array(
            'API.UsersManager.getTokenAuth' => 'disableApiIfNotBootstrapped',
            'Request.dispatch' => 'onDispatchRequest',
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
        );
    }

    public function modifyTourChallenges(&$challenges)
    {
    	foreach ($challenges as $index => $challenge) {
    		if ($challenge['id'] === 'track_data') {
    			$challenges[$index]['url'] = ''; // we can't generate menu url for tracking settings since we're not showing the menu
    			$challenges[$index]['description'] = __('In WordPress Admin go to Matomo Analytics => Settings => Tracking to embed the tracking code.', 'matomo');
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
            || !has_matomo_compatible_content_dir()
        ) {
            // console wouldn't really work in multi site mode... therefore we prefer to archive in the same request
            // WP_DEBUG also breaks things since it's logging things to stdout and then safe unserialise doesn't work
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

        if (strpos($url, 'module=API&method=API.get') !== false
            && strpos($url, '&trigger=archivephp') !== false
            && Url::isValidHost(parse_url($url, PHP_URL_HOST))) {
            // archiving query... we avoid issueing an http request for many reasons...
            // eg user might be using self signed certificate and request fails
            // eg http requests may not be allowed
            // eg because the WP user wouldn't be logged in the auth wouldn't work
            // etc.

            \Piwik\Access::doAsSuperUser(function () use ($url, &$response) {
                $urlQuery = parse_url($url, PHP_URL_QUERY);
                $request = new Request($urlQuery, array('serialize' => 1));
                $response = $request->process();
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

    public function onDispatchRequest(&$module, &$action, &$parameters)
    {
        $requestedModule = !empty($module) ? strtolower($module) : '';

        if ($requestedModule === 'login') {
            if ($action === 'ajaxNoAccess' || $action === 'bruteForceLog') {
                return; // allowed
            }
            throw new Exception('This feature is not available');
        }

        if (($requestedModule === 'corepluginsadmin' && $action !== 'safemode')
            || $requestedModule === 'installation'
            || $requestedModule === 'coreupdater') {
            throw new Exception('This feature is not available');
        }

        $blockedActions = array(
            array('coreadminhome', 'trackingcodegenerator'),
            array('usersmanager', 'index'),
            array('usersmanager', ''),
            array('usersmanager', 'usersettings'),
            array('sitesmanager', ''),
            array('sitesmanager', 'globalsettings'),
            array('feedback', ''),
            array('feedback', 'index'),
            array('diagnostics', 'configfile'),
            array('api', 'listallapi'),
        );
        $requestedAction = !empty($action) ? strtolower($action) : '';

        foreach ($blockedActions as $blockedAction) {
            if ($requestedModule === $blockedAction[0] && $requestedAction == $blockedAction[1]) {
                throw new Exception('This feature is not available');
            }
        }
        
        if ($requestedModule === 'sitesmanager' && $requestedAction === 'sitewithoutdata') {
            // we don't want the no data message to appear as it contains integration instructions which aren't needed
            // and links to not existing sites
            $module = 'CoreHome';
            $action = 'index';
        }
    }

    public function noAccess(Exception $exception)
    {
        if (Common::isXmlHttpRequest()) {
            $frontController = FrontController::getInstance();
            echo $frontController->dispatch('Login', 'ajaxNoAccess', array($exception->getMessage()));
            return;
        }

        wp_redirect(wp_login_url(\WpMatomo\Admin\Menu::get_reporting_url()));
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

}
