<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\SitesManager;

use Piwik\Access;
use Piwik\API\Request;
use Piwik\Common;
use Piwik\Config;
use Piwik\Container\StaticContainer;
use Piwik\Exception\UnexpectedWebsiteFoundException;
use Piwik\Option;
use Piwik\Piwik;
use Piwik\Plugins\CoreHome\SystemSummary;
use Piwik\Settings\Storage\Backend\MeasurableSettingsTable;
use Piwik\Tracker\Cache;
use Piwik\Tracker\Model as TrackerModel;
use Piwik\Session\SessionNamespace;

/**
 *
 */
class SitesManager extends \Piwik\Plugin
{
    const KEEP_URL_FRAGMENT_USE_DEFAULT = 0;
    const KEEP_URL_FRAGMENT_YES = 1;
    const KEEP_URL_FRAGMENT_NO = 2;

    /**
     * @see \Piwik\Plugin::registerEvents
     */
    public function registerEvents()
    {
        return array(
            'AssetManager.getJavaScriptFiles'        => 'getJsFiles',
            'AssetManager.getStylesheetFiles'        => 'getStylesheetFiles',
            'Tracker.Cache.getSiteAttributes'        => array('function' => 'recordWebsiteDataInCache', 'before' => true),
            'Tracker.setTrackerCacheGeneral'         => 'setTrackerCacheGeneral',
            'Translate.getClientSideTranslationKeys' => 'getClientSideTranslationKeys',
            'SitesManager.deleteSite.end'            => 'onSiteDeleted',
            'System.addSystemSummaryItems'           => 'addSystemSummaryItems',
            'Request.dispatch'                       => 'redirectDashboardToWelcomePage',
        );
    }

    public static function isSitesAdminEnabled()
    {
        return (bool) Config::getInstance()->General['enable_sites_admin'];
    }

    public static function dieIfSitesAdminIsDisabled()
    {
        Piwik::checkUserIsNotAnonymous();
        if (!self::isSitesAdminEnabled()) {
            throw new \Exception('Creating, updating, and deleting sites has been disabled.');
        }
    }

    public function addSystemSummaryItems(&$systemSummary)
    {
        if (self::isSitesAdminEnabled()) {
            $websites = Request::processRequest('SitesManager.getAllSites', array('filter_limit' => '-1'));
            $numWebsites = count($websites);
            $systemSummary[] = new SystemSummary\Item($key = 'websites', Piwik::translate('CoreHome_SystemSummaryNWebsites', $numWebsites), $value = null, $url = array('module' => 'SitesManager', 'action' => 'index'), $icon = '', $order = 10);
        }
    }

    public function redirectDashboardToWelcomePage(&$module, &$action)
    {
        if ($module !== 'CoreHome' || $action !== 'index') {
            return;
        }

        $siteId = Common::getRequestVar('idSite', false, 'int');
        if (!$siteId) {
            return;
        }

        $shouldPerformEmptySiteCheck = self::shouldPerormEmptySiteCheck($siteId);
        if (!$shouldPerformEmptySiteCheck) {
            return;
        }

        $hadTrafficKey = 'SitesManagerHadTrafficInPast_' . (int) $siteId;
        $hadTrafficBefore = Option::get($hadTrafficKey);
        if (!empty($hadTrafficBefore)) {
            // user had traffic at some stage in the past... not needed to show tracking code
            return;
        } elseif (self::hasTrackedAnyTraffic($siteId)) {
            // remember the user had traffic in the past so we won't show the tracking screen again
            // if all visits are deleted for example
            Option::set($hadTrafficKey, 1);
            return;
        } else {
            // never had any traffic
            $session = new SessionNamespace('siteWithoutData');
            if (!empty($session->ignoreMessage)) {
                return;
            }

            $module = 'SitesManager';
            $action = 'siteWithoutData';
        }
    }

    public static function hasTrackedAnyTraffic($siteId)
    {
        $trackerModel = new TrackerModel();
        return !$trackerModel->isSiteEmpty($siteId);
    }

    public static function shouldPerormEmptySiteCheck($siteId)
    {
        $shouldPerformEmptySiteCheck = true;

        /**
         * Posted before checking to display the "No data has been recorded yet" message.
         * If your Measurable should never have visits, you can use this event to make
         * sure that message is never displayed.
         *
         * @param bool &$shouldPerformEmptySiteCheck Set this value to true to perform the
         *                                           check, false if otherwise.
         * @param int $siteId The ID of the site we would perform a check for.
         */
        Piwik::postEvent('SitesManager.shouldPerformEmptySiteCheck', [&$shouldPerformEmptySiteCheck, $siteId]);

        return $shouldPerformEmptySiteCheck;
    }

    public function onSiteDeleted($idSite)
    {
        // we do not delete logs here on purpose (you can run these queries on the log_ tables to delete all data)
        Cache::deleteCacheWebsiteAttributes($idSite);

        $archiveInvalidator = StaticContainer::get('Piwik\Archive\ArchiveInvalidator');
        $archiveInvalidator->forgetRememberedArchivedReportsToInvalidateForSite($idSite);

        MeasurableSettingsTable::removeAllSettingsForSite($idSite);
    }

    /**
     * Get CSS files
     */
    public function getStylesheetFiles(&$stylesheets)
    {
        $stylesheets[] = "plugins/SitesManager/stylesheets/SitesManager.less";
    }

    /**
     * Get JavaScript files
     */
    public function getJsFiles(&$jsFiles)
    {
        $jsFiles[] = "plugins/SitesManager/angularjs/sites-manager/api-helper.service.js";
        $jsFiles[] = "plugins/SitesManager/angularjs/sites-manager/api-site.service.js";
        $jsFiles[] = "plugins/SitesManager/angularjs/sites-manager/api-core.service.js";
        $jsFiles[] = "plugins/SitesManager/angularjs/sites-manager/sites-manager-type-model.js";
        $jsFiles[] = "plugins/SitesManager/angularjs/sites-manager/sites-manager-admin-sites-model.js";
        $jsFiles[] = "plugins/SitesManager/angularjs/sites-manager/multiline-field.directive.js";
        $jsFiles[] = "plugins/SitesManager/angularjs/sites-manager/edit-trigger.directive.js";
        $jsFiles[] = "plugins/SitesManager/angularjs/sites-manager/sites-manager.controller.js";
        $jsFiles[] = "plugins/SitesManager/angularjs/sites-manager/sites-manager-site.controller.js";
    }

    /**
     * Hooks when a website tracker cache is flushed (website updated, cache deleted, or empty cache)
     * Will record in the tracker config file all data needed for this website in Tracker.
     *
     * @param array $array
     * @param int $idSite
     * @return void
     */
    public function recordWebsiteDataInCache(&$array, $idSite)
    {
        $idSite = (int) $idSite;

        $website = API::getInstance()->getSiteFromId($idSite);
        $urls = API::getInstance()->getSiteUrlsFromId($idSite);

        // add the 'hosts' entry in the website array
        $array['urls']  = $urls;
        $array['hosts'] = $this->getTrackerHosts($urls);

        $array['exclude_unknown_urls'] = $website['exclude_unknown_urls'];
        $array['excluded_ips'] = $this->getTrackerExcludedIps($website);
        $array['excluded_parameters'] = self::getTrackerExcludedQueryParameters($website);
        $array['excluded_user_agents'] = self::getExcludedUserAgents($website);
        $array['keep_url_fragment'] = self::shouldKeepURLFragmentsFor($website);
        $array['sitesearch'] = $website['sitesearch'];
        $array['sitesearch_keyword_parameters'] = $this->getTrackerSearchKeywordParameters($website);
        $array['sitesearch_category_parameters'] = $this->getTrackerSearchCategoryParameters($website);
        $array['timezone'] = $this->getTimezoneFromWebsite($website);
        $array['ts_created'] = $website['ts_created'];
        $array['type'] = $website['type'];
    }

    public function setTrackerCacheGeneral(&$cache)
    {
        Access::doAsSuperUser(function () use (&$cache) {
            $cache['global_excluded_user_agents'] = self::filterBlankFromCommaSepList(API::getInstance()->getExcludedUserAgentsGlobal());
            $cache['global_excluded_ips'] = self::filterBlankFromCommaSepList(API::getInstance()->getExcludedIpsGlobal());
        });
    }

    /**
     * Returns whether we should keep URL fragments for a specific site.
     *
     * @param array $site DB data for the site.
     * @return bool
     */
    private static function getTimezoneFromWebsite($site)
    {
        if (!empty($site['timezone'])) {
            return $site['timezone'];
        }
    }

    /**
     * Returns whether we should keep URL fragments for a specific site.
     *
     * @param array $site DB data for the site.
     * @return bool
     */
    private static function shouldKeepURLFragmentsFor($site)
    {
        if ($site['keep_url_fragment'] == self::KEEP_URL_FRAGMENT_YES) {
            return true;
        } else if ($site['keep_url_fragment'] == self::KEEP_URL_FRAGMENT_NO) {
            return false;
        }

        return API::getInstance()->getKeepURLFragmentsGlobal();
    }

    private function getTrackerSearchKeywordParameters($website)
    {
        $searchParameters = $website['sitesearch_keyword_parameters'];
        if (empty($searchParameters)) {
            $searchParameters = API::getInstance()->getSearchKeywordParametersGlobal();
        }
        return explode(",", $searchParameters);
    }

    private function getTrackerSearchCategoryParameters($website)
    {
        $searchParameters = $website['sitesearch_category_parameters'];
        if (empty($searchParameters)) {
            $searchParameters = API::getInstance()->getSearchCategoryParametersGlobal();
        }
        return explode(",", $searchParameters);
    }

    /**
     * Returns the array of excluded IPs to save in the config file
     *
     * @param array $website
     * @return array
     */
    private function getTrackerExcludedIps($website)
    {
        $excludedIps = $website['excluded_ips'];
        $globalExcludedIps = API::getInstance()->getExcludedIpsGlobal();

        $excludedIps .= ',' . $globalExcludedIps;

        $ipRanges = array();
        foreach (explode(',', $excludedIps) as $ip) {
            $ipRange = API::getInstance()->getIpsForRange($ip);
            if ($ipRange !== false) {
                $ipRanges[] = $ipRange;
            }
        }
        return $ipRanges;
    }

    /**
     * Returns the array of excluded user agent substrings for a site. Filters out
     * any garbage data & trims each entry.
     *
     * @param array $website The full set of information for a site.
     * @return array
     */
    private static function getExcludedUserAgents($website)
    {
        $excludedUserAgents = API::getInstance()->getExcludedUserAgentsGlobal();
        if (API::getInstance()->isSiteSpecificUserAgentExcludeEnabled()) {
            $excludedUserAgents .= ',' . $website['excluded_user_agents'];
        }
        return self::filterBlankFromCommaSepList($excludedUserAgents);
    }

    /**
     * Returns the array of URL query parameters to exclude from URLs
     *
     * @param array $website
     * @return array
     */
    public static function getTrackerExcludedQueryParameters($website)
    {
        $excludedQueryParameters = $website['excluded_parameters'];
        $globalExcludedQueryParameters = API::getInstance()->getExcludedQueryParametersGlobal();

        $excludedQueryParameters .= ',' . $globalExcludedQueryParameters;
        return self::filterBlankFromCommaSepList($excludedQueryParameters);
    }

    /**
     * Trims each element of a comma-separated list of strings, removes empty elements and
     * returns the result (as an array).
     *
     * @param string $parameters The unfiltered list.
     * @return array The filtered list of strings as an array.
     */
    private static function filterBlankFromCommaSepList($parameters)
    {
        $parameters = explode(',', $parameters);
        $parameters = array_filter($parameters, 'strlen');
        $parameters = array_unique($parameters);
        return $parameters;
    }

    /**
     * Returns the hosts alias URLs
     * @param int $idSite
     * @return array
     */
    private function getTrackerHosts($urls)
    {
        $hosts = array();
        foreach ($urls as $url) {
            $url = parse_url($url);
            if (isset($url['host'])) {
                $hosts[] = $url['host'];
            }
        }
        return $hosts;
    }

    public function getClientSideTranslationKeys(&$translationKeys)
    {
        $translationKeys[] = "General_Save";
        $translationKeys[] = "General_OrCancel";
        $translationKeys[] = "General_Actions";
        $translationKeys[] = "General_Search";
        $translationKeys[] = "General_Previous";
        $translationKeys[] = "General_Next";
        $translationKeys[] = "General_Pagination";
        $translationKeys[] = "General_Cancel";
        $translationKeys[] = "General_ClickToSearch";
        $translationKeys[] = "General_PaginationWithoutTotal";
        $translationKeys[] = "General_Loading";
        $translationKeys[] = "Actions_SubmenuSitesearch";
        $translationKeys[] = "SitesManager_OnlyOneSiteAtTime";
        $translationKeys[] = "SitesManager_DeleteConfirm";
        $translationKeys[] = "SitesManager_Urls";
        $translationKeys[] = "SitesManager_ExcludedIps";
        $translationKeys[] = "SitesManager_ExcludedParameters";
        $translationKeys[] = "SitesManager_ExcludedUserAgents";
        $translationKeys[] = "SitesManager_Timezone";
        $translationKeys[] = "SitesManager_Currency";
        $translationKeys[] = "SitesManager_ShowTrackingTag";
        $translationKeys[] = "SitesManager_AliasUrlHelp";
        $translationKeys[] = "SitesManager_OnlyMatchedUrlsAllowed";
        $translationKeys[] = "SitesManager_OnlyMatchedUrlsAllowedHelp";
        $translationKeys[] = "SitesManager_OnlyMatchedUrlsAllowedHelpExamples";
        $translationKeys[] = "SitesManager_KeepURLFragmentsLong";
        $translationKeys[] = "SitesManager_HelpExcludedIpAddresses";
        $translationKeys[] = "SitesManager_ListOfQueryParametersToExclude";
        $translationKeys[] = "SitesManager_PiwikWillAutomaticallyExcludeCommonSessionParameters";
        $translationKeys[] = "SitesManager_GlobalExcludedUserAgentHelp1";
        $translationKeys[] = "SitesManager_GlobalListExcludedUserAgents_Desc";
        $translationKeys[] = "SitesManager_GlobalExcludedUserAgentHelp2";
        $translationKeys[] = "SitesManager_WebsitesManagement";
        $translationKeys[] = "SitesManager_MainDescription";
        $translationKeys[] = "SitesManager_YouCurrentlyHaveAccessToNWebsites";
        $translationKeys[] = "SitesManager_SuperUserAccessCan";
        $translationKeys[] = "SitesManager_EnableSiteSearch";
        $translationKeys[] = "SitesManager_DisableSiteSearch";
        $translationKeys[] = "SitesManager_SearchUseDefault";
        $translationKeys[] = "SitesManager_Sites";
        $translationKeys[] = "SitesManager_SiteSearchUse";
        $translationKeys[] = "SitesManager_SearchKeywordLabel";
        $translationKeys[] = "SitesManager_SearchCategoryLabel";
        $translationKeys[] = "SitesManager_YourCurrentIpAddressIs";
        $translationKeys[] = "SitesManager_SearchKeywordParametersDesc";
        $translationKeys[] = "SitesManager_SearchCategoryParametersDesc";
        $translationKeys[] = "SitesManager_CurrencySymbolWillBeUsedForGoals";
        $translationKeys[] = "SitesManager_ChangingYourTimezoneWillOnlyAffectDataForward";
        $translationKeys[] = "SitesManager_AdvancedTimezoneSupportNotFound";
        $translationKeys[] = "SitesManager_UTCTimeIs";
        $translationKeys[] = "SitesManager_EnableEcommerce";
        $translationKeys[] = "SitesManager_NotAnEcommerceSite";
        $translationKeys[] = "SitesManager_EcommerceHelp";
        $translationKeys[] = "SitesManager_PiwikOffersEcommerceAnalytics";
        $translationKeys[] = "SitesManager_GlobalWebsitesSettings";
        $translationKeys[] = "SitesManager_GlobalListExcludedIps";
        $translationKeys[] = "SitesManager_ListOfIpsToBeExcludedOnAllWebsites";
        $translationKeys[] = "SitesManager_GlobalListExcludedQueryParameters";
        $translationKeys[] = "SitesManager_ListOfQueryParametersToBeExcludedOnAllWebsites";
        $translationKeys[] = "SitesManager_GlobalListExcludedUserAgents";
        $translationKeys[] = "SitesManager_EnableSiteSpecificUserAgentExclude_Help";
        $translationKeys[] = "SitesManager_EnableSiteSpecificUserAgentExclude";
        $translationKeys[] = "SitesManager_KeepURLFragments";
        $translationKeys[] = "SitesManager_KeepURLFragmentsHelp";
        $translationKeys[] = "SitesManager_KeepURLFragmentsHelp2";
        $translationKeys[] = "SitesManager_TrackingSiteSearch";
        $translationKeys[] = "SitesManager_SearchParametersNote";
        $translationKeys[] = "SitesManager_SearchParametersNote2";
        $translationKeys[] = "SitesManager_SearchCategoryDesc";
        $translationKeys[] = "SitesManager_DefaultTimezoneForNewWebsites";
        $translationKeys[] = "SitesManager_SelectDefaultTimezone";
        $translationKeys[] = "SitesManager_DefaultCurrencyForNewWebsites";
        $translationKeys[] = "SitesManager_SelectDefaultCurrency";
        $translationKeys[] = "SitesManager_AddMeasurable";
        $translationKeys[] = "SitesManager_AddSite";
        $translationKeys[] = "SitesManager_XManagement";
        $translationKeys[] = "SitesManager_ChooseMeasurableTypeHeadline";
        $translationKeys[] = "SitesManager_Type";
        $translationKeys[] = "General_Measurables";
        $translationKeys[] = "Goals_Ecommerce";
        $translationKeys[] = "SitesManager_NotFound";
        $translationKeys[] = "SitesManager_DeleteSiteExplanation";
        $translationKeys[] = "SitesManager_EmailInstructionsButton";
        $translationKeys[] = "SitesManager_EmailInstructionsSubject";
        $translationKeys[] = "SitesManager_JsTrackingTagHelp";
    }
}
