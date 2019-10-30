<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\SitesManager;

use DateTimeZone;
use Exception;
use Piwik\Access;
use Piwik\Common;
use Piwik\Container\StaticContainer;
use Piwik\Date;
use Piwik\Network\IPUtils;
use Piwik\Option;
use Piwik\Piwik;
use Piwik\Plugin\SettingsProvider;
use Piwik\Plugins\CorePluginsAdmin\SettingsMetadata;
use Piwik\Plugins\WebsiteMeasurable\Settings\Urls;
use Piwik\Settings\Measurable\MeasurableProperty;
use Piwik\Settings\Measurable\MeasurableSettings;
use Piwik\ProxyHttp;
use Piwik\Scheduler\Scheduler;
use Piwik\SettingsPiwik;
use Piwik\SettingsServer;
use Piwik\Site;
use Piwik\Tracker;
use Piwik\Tracker\Cache;
use Piwik\Tracker\TrackerCodeGenerator;
use Piwik\Measurable\Type;
use Piwik\Url;
use Piwik\UrlHelper;

/**
 * The SitesManager API gives you full control on Websites in Matomo (create, update and delete), and many methods to retrieve websites based on various attributes.
 *
 * This API lets you create websites via "addSite", update existing websites via "updateSite" and delete websites via "deleteSite".
 * When creating websites, it can be useful to access internal codes used by Matomo for currencies via "getCurrencyList", or timezones via "getTimezonesList".
 *
 * There are also many ways to request a list of websites: from the website ID via "getSiteFromId" or the site URL via "getSitesIdFromSiteUrl".
 * Often, the most useful technique is to list all websites that are known to a current user, based on the token_auth, via
 * "getSitesWithAdminAccess", "getSitesWithViewAccess" or "getSitesWithAtLeastViewAccess" (which returns both).
 *
 * Some methods will affect all websites globally: "setGlobalExcludedIps" will set the list of IPs to be excluded on all websites,
 * "setGlobalExcludedQueryParameters" will set the list of URL parameters to remove from URLs for all websites.
 * The existing values can be fetched via "getExcludedIpsGlobal" and "getExcludedQueryParametersGlobal".
 * See also the documentation about <a href='http://matomo.org/docs/manage-websites/' rel='noreferrer' target='_blank'>Managing Websites</a> in Matomo.
 * @method static \Piwik\Plugins\SitesManager\API getInstance()
 */
class API extends \Piwik\Plugin\API
{
    const DEFAULT_SEARCH_KEYWORD_PARAMETERS = 'q,query,s,search,searchword,k,keyword';
    const OPTION_EXCLUDED_IPS_GLOBAL = 'SitesManager_ExcludedIpsGlobal';
    const OPTION_DEFAULT_TIMEZONE = 'SitesManager_DefaultTimezone';
    const OPTION_DEFAULT_CURRENCY = 'SitesManager_DefaultCurrency';
    const OPTION_EXCLUDED_QUERY_PARAMETERS_GLOBAL = 'SitesManager_ExcludedQueryParameters';
    const OPTION_SEARCH_KEYWORD_QUERY_PARAMETERS_GLOBAL = 'SitesManager_SearchKeywordParameters';
    const OPTION_SEARCH_CATEGORY_QUERY_PARAMETERS_GLOBAL = 'SitesManager_SearchCategoryParameters';
    const OPTION_EXCLUDED_USER_AGENTS_GLOBAL = 'SitesManager_ExcludedUserAgentsGlobal';
    const OPTION_SITE_SPECIFIC_USER_AGENT_EXCLUDE_ENABLE = 'SitesManager_EnableSiteSpecificUserAgentExclude';
    const OPTION_KEEP_URL_FRAGMENTS_GLOBAL = 'SitesManager_KeepURLFragmentsGlobal';

    /**
     * @var SettingsProvider
     */
    private $settingsProvider;

    /**
     * @var SettingsMetadata
     */
    private $settingsMetadata;

    /**
     * @var Type\TypeManager
     */
    private $typeManager;

    public function __construct(SettingsProvider $provider, SettingsMetadata $settingsMetadata, Type\TypeManager $typeManager)
    {
        $this->settingsProvider = $provider;
        $this->settingsMetadata = $settingsMetadata;
        $this->typeManager = $typeManager;
    }

    /**
     * Returns the javascript tag for the given idSite.
     * This tag must be included on every page to be tracked by Matomo
     *
     * @param int $idSite
     * @param string $piwikUrl
     * @param bool $mergeSubdomains
     * @param bool $groupPageTitlesByDomain
     * @param bool $mergeAliasUrls
     * @param bool $visitorCustomVariables
     * @param bool $pageCustomVariables
     * @param bool $customCampaignNameQueryParam
     * @param bool $customCampaignKeywordParam
     * @param bool $doNotTrack
     * @param bool $disableCookies
     * @param bool $trackNoScript
     * @param bool $forceMatomoEndpoint Whether the Matomo endpoint should be forced if Matomo was installed prior 3.7.0.
     * @return string The Javascript tag ready to be included on the HTML pages
     */
    public function getJavascriptTag($idSite, $piwikUrl = '', $mergeSubdomains = false, $groupPageTitlesByDomain = false,
                                     $mergeAliasUrls = false, $visitorCustomVariables = false, $pageCustomVariables = false,
                                     $customCampaignNameQueryParam = false, $customCampaignKeywordParam = false,
                                     $doNotTrack = false, $disableCookies = false, $trackNoScript = false,
                                     $crossDomain = false, $forceMatomoEndpoint = false)
    {
        Piwik::checkUserHasViewAccess($idSite);

        if (empty($piwikUrl)) {
            $piwikUrl = SettingsPiwik::getPiwikUrl();
        }

        // Revert the automatic encoding
        // TODO remove that when https://github.com/piwik/piwik/issues/4231 is fixed
        $piwikUrl = Common::unsanitizeInputValue($piwikUrl);
        $visitorCustomVariables = Common::unsanitizeInputValues($visitorCustomVariables);
        $pageCustomVariables = Common::unsanitizeInputValues($pageCustomVariables);
        $customCampaignNameQueryParam = Common::unsanitizeInputValue($customCampaignNameQueryParam);
        $customCampaignKeywordParam = Common::unsanitizeInputValue($customCampaignKeywordParam);

        $generator = new TrackerCodeGenerator();
        if ($forceMatomoEndpoint) {
            $generator->forceMatomoEndpoint();
        }

        $code = $generator->generate($idSite, $piwikUrl, $mergeSubdomains, $groupPageTitlesByDomain,
                                     $mergeAliasUrls, $visitorCustomVariables, $pageCustomVariables,
                                     $customCampaignNameQueryParam, $customCampaignKeywordParam,
                                     $doNotTrack, $disableCookies, $trackNoScript, $crossDomain);
        $code = str_replace(array('<br>', '<br />', '<br/>'), '', $code);
        return $code;
    }

    /**
     * Returns image link tracking code for a given site with specified options.
     *
     * @param int $idSite The ID to generate tracking code for.
     * @param string $piwikUrl The domain and URL path to the Matomo installation.
     * @param int $idGoal An ID for a goal to trigger a conversion for.
     * @param int $revenue The revenue of the goal conversion. Only used if $idGoal is supplied.
     * @param bool $forceMatomoEndpoint Whether the Matomo endpoint should be forced if Matomo was installed prior 3.7.0.
     * @return string The HTML tracking code.
     */
    public function getImageTrackingCode($idSite, $piwikUrl = '', $actionName = false, $idGoal = false, $revenue = false, $forceMatomoEndpoint = false)
    {
        $urlParams = array('idsite' => $idSite, 'rec' => 1);

        if ($actionName !== false) {
            $urlParams['action_name'] = urlencode(Common::unsanitizeInputValue($actionName));
        }

        if ($idGoal !== false) {
            $urlParams['idgoal'] = $idGoal;
            if ($revenue !== false) {
                $urlParams['revenue'] = $revenue;
            }
        }

        /**
         * Triggered when generating image link tracking code server side. Plugins can use
         * this event to customise the image tracking code that is displayed to the
         * user.
         *
         * @param string &$piwikHost The domain and URL path to the Matomo installation, eg,
         *                           `'examplepiwik.com/path/to/piwik'`.
         * @param array &$urlParams The query parameters used in the <img> element's src
         *                          URL. See Matomo's image tracking docs for more info.
         */
        Piwik::postEvent('SitesManager.getImageTrackingCode', array(&$piwikUrl, &$urlParams));

        $trackerCodeGenerator = new TrackerCodeGenerator();
        if ($forceMatomoEndpoint) {
            $trackerCodeGenerator->forceMatomoEndpoint();
        }
        $matomoPhp = $trackerCodeGenerator->getPhpTrackerEndpoint();

        $url = (ProxyHttp::isHttps() ? "https://" : "http://") . rtrim($piwikUrl, '/') . '/'.$matomoPhp.'?' . Url::getQueryStringFromParameters($urlParams);
        $html = "<!-- Matomo Image Tracker-->
<img src=\"" . htmlspecialchars($url, ENT_COMPAT, 'UTF-8') . "\" style=\"border:0\" alt=\"\" />
<!-- End Matomo -->";
        return htmlspecialchars($html, ENT_COMPAT, 'UTF-8');
    }

    /**
     * Returns all websites belonging to the specified group
     * @param string $group Group name
     * @return array of sites
     */
    public function getSitesFromGroup($group = '')
    {
        Piwik::checkUserHasSuperUserAccess();

        $group = trim($group);
        $sites = $this->getModel()->getSitesFromGroup($group);

        foreach ($sites as &$site) {
            $this->enrichSite($site);
        }

        $sites = Site::setSitesFromArray($sites);
        return $sites;
    }

    /**
     * Returns the list of website groups, including the empty group
     * if no group were specified for some websites
     *
     * @return array of group names strings
     */
    public function getSitesGroups()
    {
        Piwik::checkUserHasSuperUserAccess();

        $groups = $this->getModel()->getSitesGroups();
        $cleanedGroups = array_map('trim', $groups);

        return $cleanedGroups;
    }

    /**
     * Returns the website information : name, main_url
     *
     * @throws Exception if the site ID doesn't exist or the user doesn't have access to it
     * @param int $idSite
     * @return array
     */
    public function getSiteFromId($idSite)
    {
        Piwik::checkUserHasViewAccess($idSite);

        $site = $this->getModel()->getSiteFromId($idSite);

        if ($site) {
            $this->enrichSite($site);
        }

        Site::setSiteFromArray($idSite, $site);

        return $site;
    }

    private function getModel()
    {
        return new Model();
    }

    /**
     * Returns the list of all URLs registered for the given idSite (main_url + alias URLs).
     *
     * @throws Exception if the website ID doesn't exist or the user doesn't have access to it
     * @param int $idSite
     * @return array list of URLs
     */
    public function getSiteUrlsFromId($idSite)
    {
        Piwik::checkUserHasViewAccess($idSite);
        return $this->getModel()->getSiteUrlsFromId($idSite);
    }

    private function getSitesId()
    {
        return $this->getModel()->getSitesId();
    }

    /**
     * Returns all websites, requires Super User access
     *
     * @return array The list of websites, indexed by idsite
     */
    public function getAllSites()
    {
        Piwik::checkUserHasSuperUserAccess();

        $sites  = $this->getModel()->getAllSites();
        $return = array();
        foreach ($sites as $site) {
            $this->enrichSite($site);
            $return[$site['idsite']] = $site;
        }

        $return = Site::setSitesFromArray($return);

        return $return;
    }

    /**
     * Returns the list of all the website IDs registered.
     * Requires Super User access.
     *
     * @return array The list of website IDs
     */
    public function getAllSitesId()
    {
        Piwik::checkUserHasSuperUserAccess();
        try {
            return $this->getSitesId();
        } catch (Exception $e) {
            // can be called before Matomo tables are created so return empty
            return array();
        }
    }

    /**
     * Returns the list of the website IDs that received some visits since the specified timestamp.
     * Requires Super User access.
     *
     * @param bool|int $timestamp
     * @return array The list of website IDs
     * @deprecated since 2.15 This method will be removed in Matomo 3.0, there is no replacement.
     */
    public function getSitesIdWithVisits($timestamp = false)
    {
        Piwik::checkUserHasSuperUserAccess();

        if (empty($timestamp)) $timestamp = time();

        $time   = Date::factory((int)$timestamp)->getDatetime();
        $now    = Date::now()->addHour(1)->getDatetime();

        $result = $this->getModel()->getSitesWithVisits($time, $now);

        $idSites = array();
        foreach ($result as $idSite) {
            $idSites[] = $idSite['idsite'];
        }

        return $idSites;
    }

    /**
     * Returns the list of websites with the 'admin' access for the current user.
     * For the superUser it returns all the websites in the database.
     *
     * @param bool $fetchAliasUrls
     * @param false|string $pattern
     * @param false|int    $limit
     * @return array for each site, an array of information (idsite, name, main_url, etc.)
     */
    public function getSitesWithAdminAccess($fetchAliasUrls = false, $pattern = false, $limit = false)
    {
        $sitesId = $this->getSitesIdWithAdminAccess();

        if ($pattern === false) {
            $sites = $this->getSitesFromIds($sitesId, $limit);
        } else {
            $sites = $this->getModel()->getPatternMatchSites($sitesId, $pattern, $limit);

            foreach ($sites as &$site) {
                $this->enrichSite($site);
            }

            $sites = Site::setSitesFromArray($sites);
        }

        if ($fetchAliasUrls) {
            foreach ($sites as &$site) {
                $site['alias_urls'] = $this->getSiteUrlsFromId($site['idsite']);
            }
        }

        return $sites;
    }

    /**
     * Returns the list of websites with the 'view' access for the current user.
     * For the superUser it doesn't return any result because the superUser has admin access on all the websites (use getSitesWithAtLeastViewAccess() instead).
     *
     * @return array for each site, an array of information (idsite, name, main_url, etc.)
     */
    public function getSitesWithViewAccess()
    {
        $sitesId = $this->getSitesIdWithViewAccess();
        return $this->getSitesFromIds($sitesId);
    }

    /**
     * Returns the list of websites with the 'view' or 'admin' access for the current user.
     * For the superUser it returns all the websites in the database.
     *
     * @param bool|int $limit Specify max number of sites to return
     * @param bool $_restrictSitesToLogin Hack necessary when running scheduled tasks, where "Super User" is forced, but sometimes not desired, see #3017
     * @return array array for each site, an array of information (idsite, name, main_url, etc.)
     */
    public function getSitesWithAtLeastViewAccess($limit = false, $_restrictSitesToLogin = false)
    {
        $sitesId = $this->getSitesIdWithAtLeastViewAccess($_restrictSitesToLogin);
        return $this->getSitesFromIds($sitesId, $limit);
    }

    /**
     * Returns the list of websites ID with the 'admin' access for the current user.
     * For the superUser it returns all the websites in the database.
     *
     * @return array list of websites ID
     */
    public function getSitesIdWithAdminAccess()
    {
        $sitesId = Access::getInstance()->getSitesIdWithAdminAccess();
        return $sitesId;
    }

    /**
     * Returns the list of websites ID with the 'view' access for the current user.
     * For the superUser it doesn't return any result because the superUser has admin access on all the websites (use getSitesIdWithAtLeastViewAccess() instead).
     *
     * @return array list of websites ID
     */
    public function getSitesIdWithViewAccess()
    {
        return Access::getInstance()->getSitesIdWithViewAccess();
    }

    /**
     * Returns the list of websites ID with the 'write' access for the current user.
     * For the superUser it doesn't return any result because the superUser has write access on all the websites (use getSitesIdWithAtLeastWriteAccess() instead).
     *
     * @return array list of websites ID
     */
    public function getSitesIdWithWriteAccess()
    {
        return Access::getInstance()->getSitesIdWithWriteAccess();
    }

    /**
     * Returns the list of websites ID with the 'view' or 'admin' access for the current user.
     * For the superUser it returns all the websites in the database.
     *
     * @param bool $_restrictSitesToLogin
     * @return array list of websites ID
     */
    public function getSitesIdWithAtLeastViewAccess($_restrictSitesToLogin = false)
    {
        /** @var Scheduler $scheduler */
        $scheduler = StaticContainer::getContainer()->get('Piwik\Scheduler\Scheduler');

        if (Piwik::hasUserSuperUserAccess() && !$scheduler->isRunningTask()) {
            return Access::getInstance()->getSitesIdWithAtLeastViewAccess();
        }

        if (!empty($_restrictSitesToLogin)
            // Only Super User or logged in user can see viewable sites for a specific login,
            // but during scheduled task execution, we sometimes want to restrict sites to
            // a different login than the superuser.
            && (Piwik::hasUserSuperUserAccessOrIsTheUser($_restrictSitesToLogin)
                || $scheduler->isRunningTask())
        ) {

            if (Piwik::hasTheUserSuperUserAccess($_restrictSitesToLogin)) {
                return Access::getInstance()->getSitesIdWithAtLeastViewAccess();
            }

            $accessRaw = Access::getInstance()->getRawSitesWithSomeViewAccess($_restrictSitesToLogin);
            $sitesId   = array();

            foreach ($accessRaw as $access) {
                $sitesId[] = $access['idsite'];
            }

            return $sitesId;
        } else {
            return Access::getInstance()->getSitesIdWithAtLeastViewAccess();
        }
    }

    /**
     * Returns the list of websites from the ID array in parameters.
     * The user access is not checked in this method so the ID have to be accessible by the user!
     *
     * @param array $idSites list of website ID
     * @param bool $limit
     * @return array
     */
    private function getSitesFromIds($idSites, $limit = false)
    {
        $sites = $this->getModel()->getSitesFromIds($idSites, $limit);

        foreach ($sites as &$site) {
            $this->enrichSite($site);
        }

        $sites = Site::setSitesFromArray($sites);

        return $sites;
    }

    protected function getNormalizedUrls($url)
    {
        // if found, remove scheme and www. from URL
        $hostname = str_replace('www.', '', $url);
        $hostname = str_replace('http://', '', $hostname);
        $hostname = str_replace('https://', '', $hostname);

        // return all variations of the URL
        return array(
            $url,
            "http://" . $hostname,
            "http://www." . $hostname,
            "https://" . $hostname,
            "https://www." . $hostname
        );
    }

    /**
     * Returns the list of websites ID associated with a URL.
     *
     * @param string $url
     * @return array list of websites ID
     */
    public function getSitesIdFromSiteUrl($url)
    {
        $url = $this->removeTrailingSlash($url);
        $normalisedUrls = $this->getNormalizedUrls($url);

        if (Piwik::hasUserSuperUserAccess()) {
            $ids   = $this->getModel()->getAllSitesIdFromSiteUrl($normalisedUrls);
        } else {
            $login = Piwik::getCurrentUserLogin();
            $ids   = $this->getModel()->getSitesIdFromSiteUrlHavingAccess($login, $normalisedUrls);
        }

        return $ids;
    }

    /**
     * Returns all websites with a timezone matching one the specified timezones
     *
     * @param array $timezones
     * @return array
     * @ignore
     */
    public function getSitesIdFromTimezones($timezones)
    {
        Piwik::checkUserHasSuperUserAccess();

        $timezones = Piwik::getArrayFromApiParameter($timezones);
        $timezones = array_unique($timezones);

        $ids = $this->getModel()->getSitesFromTimezones($timezones);

        $return = array();
        foreach ($ids as $id) {
            $return[] = $id['idsite'];
        }

        return $return;
    }

    private function enrichSite(&$site)
    {
        $site['timezone_name'] = $this->getTimezoneName($site['timezone']);

        $key = 'Intl_Currency_' . $site['currency'];
        $name = Piwik::translate($key);

        $site['currency_name'] = ($key === $name) ? $site['currency'] : $name;

        // don't want to expose other user logins here
        if (!Piwik::hasUserSuperUserAccess()) {
            unset($site['creator_login']);
        }
    }

    /**
     * Add a website.
     * Requires Super User access.
     *
     * The website is defined by a name and an array of URLs.
     * @param string $siteName Site name
     * @param array|string $urls The URLs array must contain at least one URL called the 'main_url' ;
     *                        if several URLs are provided in the array, they will be recorded
     *                        as Alias URLs for this website.
     *                        When calling API via HTTP specify multiple URLs via `&urls[]=http...&urls[]=http...`.
     * @param int $ecommerce Is Ecommerce Reporting enabled for this website?
     * @param null $siteSearch
     * @param string $searchKeywordParameters Comma separated list of search keyword parameter names
     * @param string $searchCategoryParameters Comma separated list of search category parameter names
     * @param string $excludedIps Comma separated list of IPs to exclude from the reports (allows wildcards)
     * @param null $excludedQueryParameters
     * @param string $timezone Timezone string, eg. 'Europe/London'
     * @param string $currency Currency, eg. 'EUR'
     * @param string $group Website group identifier
     * @param string $startDate Date at which the statistics for this website will start. Defaults to today's date in YYYY-MM-DD format
     * @param null|string $excludedUserAgents
     * @param int $keepURLFragments If 1, URL fragments will be kept when tracking. If 2, they
     *                              will be removed. If 0, the default global behavior will be used.
     * @param array|null $settingValues JSON serialized settings eg {settingName: settingValue, ...}
     * @see getKeepURLFragmentsGlobal.
     * @param string $type The website type, defaults to "website" if not set.
     * @param bool|null $excludeUnknownUrls Track only URL matching one of website URLs
     *
     * @return int the website ID created
     */
    public function addSite($siteName,
                            $urls = null,
                            $ecommerce = null,
                            $siteSearch = null,
                            $searchKeywordParameters = null,
                            $searchCategoryParameters = null,
                            $excludedIps = null,
                            $excludedQueryParameters = null,
                            $timezone = null,
                            $currency = null,
                            $group = null,
                            $startDate = null,
                            $excludedUserAgents = null,
                            $keepURLFragments = null,
                            $type = null,
                            $settingValues = null,
                            $excludeUnknownUrls = null)
    {
        Piwik::checkUserHasSuperUserAccess();
        SitesManager::dieIfSitesAdminIsDisabled();

        $this->checkName($siteName);

        if (!isset($settingValues)) {
            $settingValues = array();
        }

        $coreProperties = array();
        $coreProperties = $this->setSettingValue('urls', $urls, $coreProperties, $settingValues);
        $coreProperties = $this->setSettingValue('ecommerce', $ecommerce, $coreProperties, $settingValues);
        $coreProperties = $this->setSettingValue('group', $group, $coreProperties, $settingValues);
        $coreProperties = $this->setSettingValue('sitesearch', $siteSearch, $coreProperties, $settingValues);
        $coreProperties = $this->setSettingValue('sitesearch_keyword_parameters', explode(',', $searchKeywordParameters), $coreProperties, $settingValues);
        $coreProperties = $this->setSettingValue('sitesearch_category_parameters', explode(',', $searchCategoryParameters), $coreProperties, $settingValues);
        $coreProperties = $this->setSettingValue('keep_url_fragment', $keepURLFragments, $coreProperties, $settingValues);
        $coreProperties = $this->setSettingValue('exclude_unknown_urls', $excludeUnknownUrls, $coreProperties, $settingValues);
        $coreProperties = $this->setSettingValue('excluded_ips', explode(',', $excludedIps), $coreProperties, $settingValues);
        $coreProperties = $this->setSettingValue('excluded_parameters', explode(',', $excludedQueryParameters), $coreProperties, $settingValues);
        $coreProperties = $this->setSettingValue('excluded_user_agents', explode(',', $excludedUserAgents), $coreProperties, $settingValues);

        $timezone = trim($timezone);
        if (empty($timezone)) {
            $timezone = $this->getDefaultTimezone();
        }
        $this->checkValidTimezone($timezone);

        if (empty($currency)) {
            $currency = $this->getDefaultCurrency();
        }
        $this->checkValidCurrency($currency);

        $bind = array('name' => $siteName);
        $bind['timezone']   = $timezone;
        $bind['currency']   = $currency;
        $bind['main_url']   = '';

        if (is_null($startDate)) {
            $bind['ts_created'] = Date::now()->getDatetime();
        } else {
            $bind['ts_created'] = Date::factory($startDate)->getDatetime();
        }

        $bind['type'] = $this->checkAndReturnType($type);

        if (!empty($group) && Piwik::hasUserSuperUserAccess()) {
            $bind['group'] = trim($group);
        } else {
            $bind['group'] = "";
        }

        $bind['creator_login'] = Piwik::getCurrentUserLogin();

        $allSettings = $this->setAndValidateMeasurableSettings(0, 'website', $coreProperties);

        // any setting specified in setting values will overwrite other setting
        if (!empty($settingValues)) {
            $this->setAndValidateMeasurableSettings(0, $bind['type'], $settingValues);
        }

        foreach ($allSettings as $settings) {
            foreach ($settings->getSettingsWritableByCurrentUser() as $setting) {
                $name = $setting->getName();
                if ($setting instanceof MeasurableProperty && $name !== 'urls') {
                    $default = $setting->getDefaultValue();
                    if (is_bool($default)) {
                        $default = (int) $default;
                    } elseif (is_array($default)) {
                        $default = implode(',', $default);
                    }

                    $bind[$name] = $default;
                }
            }
        }

        $idSite = $this->getModel()->createSite($bind);

        if (!empty($coreProperties)) {
            $this->saveMeasurableSettings($idSite, 'website', $coreProperties);
        }
        if (!empty($settingValues)) {
            $this->saveMeasurableSettings($idSite, $bind['type'], $settingValues);
        }

        // we reload the access list which doesn't yet take in consideration this new website
        Access::getInstance()->reloadAccess();

        $this->postUpdateWebsite($idSite);

        /**
         * Triggered after a site has been added.
         *
         * @param int $idSite The ID of the site that was added.
         */
        Piwik::postEvent('SitesManager.addSite.end', array($idSite));

        return (int) $idSite;
    }

    private function setSettingValue($fieldName, $value, $coreProperties, $settingValues)
    {
        $pluginName = 'WebsiteMeasurable';

        if (isset($value)) {

            if (empty($coreProperties[$pluginName])) {
                $coreProperties[$pluginName] = array();
            }

            $coreProperties[$pluginName][] = array('name' => $fieldName, 'value' => $value);

        } elseif (!empty($settingValues[$pluginName])) {
            // we check if the value is defined in the setting values instead
            foreach ($settingValues[$pluginName] as $key => $setting) {
                if ($setting['name'] === $fieldName) {

                    if (empty($coreProperties[$pluginName])) {
                        $coreProperties[$pluginName] = array();
                    }

                    $coreProperties[$pluginName][] = array('name' => $fieldName, 'value' => $setting['value']);
                    return $coreProperties;
                }
            }
        }

        return $coreProperties;
    }

    public function getSiteSettings($idSite)
    {
        Piwik::checkUserHasAdminAccess($idSite);

        $measurableSettings = $this->settingsProvider->getAllMeasurableSettings($idSite, $idMeasurableType = false);

        return $this->settingsMetadata->formatSettings($measurableSettings);
    }

    private function setAndValidateMeasurableSettings($idSite, $idType, $settingValues)
    {
        $measurableSettings = $this->settingsProvider->getAllMeasurableSettings($idSite, $idType);

        $this->settingsMetadata->setPluginSettings($measurableSettings, $settingValues);

        return $measurableSettings;
    }

    /**
     * @param MeasurableSettings[] $measurableSettings
     */
    private function saveMeasurableSettings($idSite, $idType, $settingValues)
    {
        $measurableSettings = $this->setAndValidateMeasurableSettings($idSite, $idType, $settingValues);

        foreach ($measurableSettings as $measurableSetting) {
            $measurableSetting->save();
        }
    }

    private function postUpdateWebsite($idSite)
    {
        Site::clearCache();
        Cache::regenerateCacheWebsiteAttributes($idSite);
        Cache::clearCacheGeneral();
        SiteUrls::clearSitesCache();
    }

    /**
     * Delete a website from the database, given its Id. The method deletes the actual site as well as some associated
     * data. However, it does not delete any logs or archives that belong to this website. You can delete logs and
     * archives for a site manually as described in this FAQ: http://matomo.org/faq/how-to/faq_73/ .
     *
     * Requires Super User access.
     *
     * @param int $idSite
     * @throws Exception
     */
    public function deleteSite($idSite)
    {
        Piwik::checkUserHasSuperUserAccess();
        SitesManager::dieIfSitesAdminIsDisabled();

        $idSites = $this->getSitesId();
        if (!in_array($idSite, $idSites)) {
            throw new Exception("website id = $idSite not found");
        }
        $nbSites = count($idSites);
        if ($nbSites == 1) {
            throw new Exception(Piwik::translate("SitesManager_ExceptionDeleteSite"));
        }

        $this->getModel()->deleteSite($idSite);

        /**
         * Triggered after a site has been deleted.
         *
         * Plugins can use this event to remove site specific values or settings, such as removing all
         * goals that belong to a specific website. If you store any data related to a website you
         * should clean up that information here.
         *
         * @param int $idSite The ID of the site being deleted.
         */
        Piwik::postEvent('SitesManager.deleteSite.end', array($idSite));
    }

    private function checkValidTimezone($timezone)
    {
        $timezones = $this->getTimezonesList();
        foreach (array_values($timezones) as $cities) {
            foreach ($cities as $timezoneId => $city) {
                if ($timezoneId == $timezone) {
                    return true;
                }
            }
        }
        throw new Exception(Piwik::translate('SitesManager_ExceptionInvalidTimezone', array($timezone)));
    }

    private function checkValidCurrency($currency)
    {
        if (!in_array($currency, array_keys($this->getCurrencyList()))) {
            throw new Exception(Piwik::translate('SitesManager_ExceptionInvalidCurrency', array($currency, "USD, EUR, etc.")));
        }
    }

    private function checkAndReturnType($type)
    {
        if (empty($type)) {
            $type = Site::DEFAULT_SITE_TYPE;
        }

        if (!is_string($type)) {
            throw new Exception("Invalid website type $type");
        }

        return $type;
    }

    /**
     * Checks that the submitted IPs (comma separated list) are valid
     * Returns the cleaned up IPs
     *
     * @param string $excludedIps Comma separated list of IP addresses
     * @throws Exception
     * @return array of IPs
     */
    private function checkAndReturnExcludedIps($excludedIps)
    {
        if (empty($excludedIps)) {
            return '';
        }

        $ips = explode(',', $excludedIps);
        $ips = array_map('trim', $ips);
        $ips = array_filter($ips, 'strlen');

        foreach ($ips as $ip) {
            if (!$this->isValidIp($ip)) {
                throw new Exception(Piwik::translate('SitesManager_ExceptionInvalidIPFormat', array($ip, "1.2.3.4, 1.2.3.*, or 1.2.3.4/5")));
            }
        }

        $ips = implode(',', $ips);
        return $ips;
    }

    /**
     * Add a list of alias Urls to the given idSite
     *
     * If some URLs given in parameter are already recorded as alias URLs for this website,
     * they won't be duplicated. The 'main_url' of the website won't be affected by this method.
     *
     * @param int $idSite
     * @param array|string $urls When calling API via HTTP specify multiple URLs via `&urls[]=http...&urls[]=http...`.
     * @return int the number of inserted URLs
     */
    public function addSiteAliasUrls($idSite, $urls)
    {
        Piwik::checkUserHasAdminAccess($idSite);

        if (empty($urls)) {
            return 0;
        }

        if (!is_array($urls)) {
            $urls = array($urls);
        }

        $urlsInit = $this->getSiteUrlsFromId($idSite);
        $toInsert = array_merge($urlsInit, $urls);

        $urlsProperty = new Urls($idSite);
        $urlsProperty->setValue($toInsert);
        $urlsProperty->save();

        $inserted = array_diff($urlsProperty->getValue(), $urlsInit);

        $this->postUpdateWebsite($idSite);

        return count($inserted);
    }

    /**
     * Set the list of alias Urls for the given idSite
     *
     * Completely overwrites the current list of URLs with the provided list.
     * The 'main_url' of the website won't be affected by this method.
     *
     * @return int the number of inserted URLs
     */
    public function setSiteAliasUrls($idSite, $urls = array())
    {
        Piwik::checkUserHasAdminAccess($idSite);

        $mainUrl = Site::getMainUrlFor($idSite);
        array_unshift($urls, $mainUrl);

        $urlsProperty = new Urls($idSite);
        $urlsProperty->setValue($urls);
        $urlsProperty->save();

        $inserted = array_diff($urlsProperty->getValue(), $urls);

        $this->postUpdateWebsite($idSite);

        return count($inserted);
    }

    /**
     * Get the start and end IP addresses for an IP address range
     *
     * @param string $ipRange IP address range in presentation format
     * @return array|false Array( low, high ) IP addresses in presentation format; or false if error
     */
    public function getIpsForRange($ipRange)
    {
        $range = IPUtils::getIPRangeBounds($ipRange);
        if ($range === null) {
            return false;
        }

        return array(IPUtils::binaryToStringIP($range[0]), IPUtils::binaryToStringIP($range[1]));
    }

    /**
     * Sets IPs to be excluded from all websites. IPs can contain wildcards.
     * Will also apply to websites created in the future.
     *
     * @param string $excludedIps Comma separated list of IPs to exclude from being tracked (allows wildcards)
     * @return bool
     */
    public function setGlobalExcludedIps($excludedIps)
    {
        Piwik::checkUserHasSuperUserAccess();
        $excludedIps = $this->checkAndReturnExcludedIps($excludedIps);
        Option::set(self::OPTION_EXCLUDED_IPS_GLOBAL, $excludedIps);
        Cache::deleteTrackerCache();
        return true;
    }

    /**
     * Sets Site Search keyword/category parameter names, to be used on websites which have not specified these values
     * Expects Comma separated list of query params names
     *
     * @param string
     * @param string
     * @return bool
     */
    public function setGlobalSearchParameters($searchKeywordParameters, $searchCategoryParameters)
    {
        Piwik::checkUserHasSuperUserAccess();
        Option::set(self::OPTION_SEARCH_KEYWORD_QUERY_PARAMETERS_GLOBAL, $searchKeywordParameters);
        Option::set(self::OPTION_SEARCH_CATEGORY_QUERY_PARAMETERS_GLOBAL, $searchCategoryParameters);
        Cache::deleteTrackerCache();
        return true;
    }

    /**
     * @return string Comma separated list of URL parameters
     */
    public function getSearchKeywordParametersGlobal()
    {
        Piwik::checkUserHasSomeAdminAccess();
        $names = Option::get(self::OPTION_SEARCH_KEYWORD_QUERY_PARAMETERS_GLOBAL);
        if ($names === false) {
            $names = self::DEFAULT_SEARCH_KEYWORD_PARAMETERS;
        }
        if (empty($names)) {
            $names = '';
        }
        return $names;
    }

    /**
     * @return string Comma separated list of URL parameters
     */
    public function getSearchCategoryParametersGlobal()
    {
        Piwik::checkUserHasSomeAdminAccess();
        return Option::get(self::OPTION_SEARCH_CATEGORY_QUERY_PARAMETERS_GLOBAL);
    }

    /**
     * Returns the list of URL query parameters that are excluded from all websites
     *
     * @return string Comma separated list of URL parameters
     */
    public function getExcludedQueryParametersGlobal()
    {
        Piwik::checkUserHasSomeViewAccess();
        return Option::get(self::OPTION_EXCLUDED_QUERY_PARAMETERS_GLOBAL);
    }

    /**
     * Returns the list of user agent substrings to look for when excluding visits for
     * all websites. If a visitor's user agent string contains one of these substrings,
     * their visits will not be included.
     *
     * @return string Comma separated list of strings.
     */
    public function getExcludedUserAgentsGlobal()
    {
        Piwik::checkUserHasSomeAdminAccess();
        return Option::get(self::OPTION_EXCLUDED_USER_AGENTS_GLOBAL);
    }

    /**
     * Sets list of user agent substrings to look for when excluding visits. For more info,
     * @see getExcludedUserAgentsGlobal.
     *
     * @param string $excludedUserAgents Comma separated list of strings. Each element is trimmed,
     *                                   and empty strings are removed.
     */
    public function setGlobalExcludedUserAgents($excludedUserAgents)
    {
        Piwik::checkUserHasSuperUserAccess();

        // update option
        $excludedUserAgents = $this->checkAndReturnCommaSeparatedStringList($excludedUserAgents);
        Option::set(self::OPTION_EXCLUDED_USER_AGENTS_GLOBAL, $excludedUserAgents);

        // make sure tracker cache will reflect change
        Cache::deleteTrackerCache();
    }

    /**
     * Returns true if site-specific user agent exclusion has been enabled. If it hasn't,
     * only the global user agent substrings (see @setGlobalExcludedUserAgents) will be used.
     *
     * @return bool
     */
    public function isSiteSpecificUserAgentExcludeEnabled()
    {
        Piwik::checkUserHasSomeAdminAccess();
        return (bool)Option::get(self::OPTION_SITE_SPECIFIC_USER_AGENT_EXCLUDE_ENABLE);
    }

    /**
     * Sets whether it should be allowed to exclude different user agents for different
     * websites.
     *
     * @param bool $enabled
     */
    public function setSiteSpecificUserAgentExcludeEnabled($enabled)
    {
        Piwik::checkUserHasSuperUserAccess();

        // update option
        Option::set(self::OPTION_SITE_SPECIFIC_USER_AGENT_EXCLUDE_ENABLE, $enabled);

        // make sure tracker cache will reflect change
        Cache::deleteTrackerCache();
    }

    /**
     * Returns true if the default behavior is to keep URL fragments when tracking,
     * false if otherwise.
     *
     * @return bool
     */
    public function getKeepURLFragmentsGlobal()
    {
        Piwik::checkUserHasSomeViewAccess();
        return (bool)Option::get(self::OPTION_KEEP_URL_FRAGMENTS_GLOBAL);
    }

    /**
     * Sets whether the default behavior should be to keep URL fragments when
     * tracking or not.
     *
     * @param $enabled bool If true, the default behavior will be to keep URL
     *                      fragments when tracking. If false, the default
     *                      behavior will be to remove them.
     */
    public function setKeepURLFragmentsGlobal($enabled)
    {
        Piwik::checkUserHasSuperUserAccess();

        // update option
        Option::set(self::OPTION_KEEP_URL_FRAGMENTS_GLOBAL, $enabled);

        // make sure tracker cache will reflect change
        Cache::deleteTrackerCache();
    }

    /**
     * Sets list of URL query parameters to be excluded on all websites.
     * Will also apply to websites created in the future.
     *
     * @param string $excludedQueryParameters Comma separated list of URL query parameters to exclude from URLs
     * @return bool
     */
    public function setGlobalExcludedQueryParameters($excludedQueryParameters)
    {
        Piwik::checkUserHasSuperUserAccess();
        $excludedQueryParameters = $this->checkAndReturnCommaSeparatedStringList($excludedQueryParameters);
        Option::set(self::OPTION_EXCLUDED_QUERY_PARAMETERS_GLOBAL, $excludedQueryParameters);
        Cache::deleteTrackerCache();
        return true;
    }

    /**
     * Returns the list of IPs that are excluded from all websites
     *
     * @return string Comma separated list of IPs
     */
    public function getExcludedIpsGlobal()
    {
        Piwik::checkUserHasSomeAdminAccess();
        return Option::get(self::OPTION_EXCLUDED_IPS_GLOBAL);
    }

    /**
     * Returns the default currency that will be set when creating a website through the API.
     *
     * @return string Currency ID eg. 'USD'
     */
    public function getDefaultCurrency()
    {
        Piwik::checkUserHasSomeAdminAccess();
        $defaultCurrency = Option::get(self::OPTION_DEFAULT_CURRENCY);
        if ($defaultCurrency) {
            return $defaultCurrency;
        }
        return 'USD';
    }

    /**
     * Sets the default currency that will be used when creating websites
     *
     * @param string $defaultCurrency Currency code, eg. 'USD'
     * @return bool
     */
    public function setDefaultCurrency($defaultCurrency)
    {
        Piwik::checkUserHasSuperUserAccess();
        $this->checkValidCurrency($defaultCurrency);
        Option::set(self::OPTION_DEFAULT_CURRENCY, $defaultCurrency);
        return true;
    }

    /**
     * Returns the default timezone that will be set when creating a website through the API.
     * Via the UI, if the default timezone is not UTC, it will be pre-selected in the drop down
     *
     * @return string Timezone eg. UTC+7 or Europe/Paris
     */
    public function getDefaultTimezone()
    {
        $defaultTimezone = Option::get(self::OPTION_DEFAULT_TIMEZONE);
        if ($defaultTimezone) {
            return $defaultTimezone;
        }
        return 'UTC';
    }

    /**
     * Sets the default timezone that will be used when creating websites
     *
     * @param string $defaultTimezone Timezone string eg. Europe/Paris or UTC+8
     * @return bool
     */
    public function setDefaultTimezone($defaultTimezone)
    {
        Piwik::checkUserHasSuperUserAccess();
        $this->checkValidTimezone($defaultTimezone);
        Option::set(self::OPTION_DEFAULT_TIMEZONE, $defaultTimezone);
        return true;
    }

    /**
     * Update an existing website.
     * If only one URL is specified then only the main url will be updated.
     * If several URLs are specified, both the main URL and the alias URLs will be updated.
     *
     * @param int $idSite website ID defining the website to edit
     * @param string $siteName website name
     * @param string|array $urls the website URLs
     *                           When calling API via HTTP specify multiple URLs via `&urls[]=http...&urls[]=http...`.
     * @param int $ecommerce Whether Ecommerce is enabled, 0 or 1
     * @param null|int $siteSearch Whether site search is enabled, 0 or 1
     * @param string $searchKeywordParameters Comma separated list of search keyword parameter names
     * @param string $searchCategoryParameters Comma separated list of search category parameter names
     * @param string $excludedIps Comma separated list of IPs to exclude from being tracked (allows wildcards)
     * @param null|string $excludedQueryParameters
     * @param string $timezone Timezone
     * @param string $currency Currency code
     * @param string $group Group name where this website belongs
     * @param string $startDate Date at which the statistics for this website will start. Defaults to today's date in YYYY-MM-DD format
     * @param null|string $excludedUserAgents
     * @param int|null $keepURLFragments If 1, URL fragments will be kept when tracking. If 2, they
     *                                   will be removed. If 0, the default global behavior will be used.
     * @param string $type The Website type, default value is "website"
     * @param array|null $settingValues JSON serialized settings eg {settingName: settingValue, ...}
     * @param bool|null $excludeUnknownUrls Track only URL matching one of website URLs
     * @throws Exception
     * @see getKeepURLFragmentsGlobal. If null, the existing value will
     *                                   not be modified.
     *
     * @return bool true on success
     */
    public function updateSite($idSite,
                               $siteName = null,
                               $urls = null,
                               $ecommerce = null,
                               $siteSearch = null,
                               $searchKeywordParameters = null,
                               $searchCategoryParameters = null,
                               $excludedIps = null,
                               $excludedQueryParameters = null,
                               $timezone = null,
                               $currency = null,
                               $group = null,
                               $startDate = null,
                               $excludedUserAgents = null,
                               $keepURLFragments = null,
                               $type = null,
                               $settingValues = null,
                               $excludeUnknownUrls = null)
    {
        Piwik::checkUserHasAdminAccess($idSite);
        SitesManager::dieIfSitesAdminIsDisabled();

        $idSites = $this->getSitesId();

        if (!in_array($idSite, $idSites)) {
            throw new Exception("website id = $idSite not found");
        }

        // Build the SQL UPDATE based on specified updates to perform
        $bind = array();

        if (!is_null($siteName)) {
            $this->checkName($siteName);
            $bind['name'] = $siteName;
        }

        if (!isset($settingValues)) {
            $settingValues = array();
        }

        if (empty($coreProperties)) {
            $coreProperties = array();
        }

        $coreProperties = $this->setSettingValue('urls', $urls, $coreProperties, $settingValues);
        $coreProperties = $this->setSettingValue('group', $group, $coreProperties, $settingValues);
        $coreProperties = $this->setSettingValue('ecommerce', $ecommerce, $coreProperties, $settingValues);
        $coreProperties = $this->setSettingValue('sitesearch', $siteSearch, $coreProperties, $settingValues);
        $coreProperties = $this->setSettingValue('sitesearch_keyword_parameters', explode(',', $searchKeywordParameters), $coreProperties, $settingValues);
        $coreProperties = $this->setSettingValue('sitesearch_category_parameters', explode(',', $searchCategoryParameters), $coreProperties, $settingValues);
        $coreProperties = $this->setSettingValue('keep_url_fragment', $keepURLFragments, $coreProperties, $settingValues);
        $coreProperties = $this->setSettingValue('exclude_unknown_urls', $excludeUnknownUrls, $coreProperties, $settingValues);
        $coreProperties = $this->setSettingValue('excluded_ips', explode(',', $excludedIps), $coreProperties, $settingValues);
        $coreProperties = $this->setSettingValue('excluded_parameters', explode(',', $excludedQueryParameters), $coreProperties, $settingValues);
        $coreProperties = $this->setSettingValue('excluded_user_agents', explode(',', $excludedUserAgents), $coreProperties, $settingValues);

        if (isset($currency)) {
            $currency = trim($currency);
            $this->checkValidCurrency($currency);
            $bind['currency'] = $currency;
        }
        if (isset($timezone)) {
            $timezone = trim($timezone);
            $this->checkValidTimezone($timezone);
            $bind['timezone'] = $timezone;
        }
        if (isset($group)
            && Piwik::hasUserSuperUserAccess()
        ) {
            $bind['group'] = trim($group);
        }
        if (isset($startDate)) {
            $bind['ts_created'] = Date::factory($startDate)->getDatetime();
        }

        if (isset($type)) {
            $bind['type'] = $this->checkAndReturnType($type);
        }

        if (!empty($coreProperties)) {
            $this->setAndValidateMeasurableSettings($idSite, $idType = 'website', $coreProperties);
        }

        if (!empty($settingValues)) {
            $this->setAndValidateMeasurableSettings($idSite, $idType = null, $settingValues);
        }

        if (!empty($bind)) {
            $this->getModel()->updateSite($bind, $idSite);
        }

        if (!empty($coreProperties)) {
            $this->saveMeasurableSettings($idSite, $idType = 'website', $coreProperties);
        }

        if (!empty($settingValues)) {
            $this->saveMeasurableSettings($idSite, $idType = null, $settingValues);
        }

        $this->postUpdateWebsite($idSite);
    }

    /**
     * Updates the field ts_created for the specified websites.
     *
     * @param $idSites int Id Site to update ts_created
     * @param $minDate Date to set as creation date. To play it safe it will subtract one more day.
     *
     * @ignore
     */
    public function updateSiteCreatedTime($idSites, Date $minDate)
    {
        $idSites = Site::getIdSitesFromIdSitesString($idSites);
        Piwik::checkUserHasAdminAccess($idSites);

        $minDateSql = $minDate->subDay(1)->getDatetime();

        $this->getModel()->updateSiteCreatedTime($idSites, $minDateSql);
    }

    private function checkAndReturnCommaSeparatedStringList($parameters)
    {
        $parameters = trim($parameters);
        if (empty($parameters)) {
            return '';
        }

        $parameters = explode(',', $parameters);
        $parameters = array_map('trim', $parameters);
        $parameters = array_filter($parameters, 'strlen');
        $parameters = array_unique($parameters);
        return implode(',', $parameters);
    }

    /**
     * Returns the list of supported currencies
     * @see getCurrencySymbols()
     * @return array ( currencyId => currencyName)
     */
    public function getCurrencyList()
    {
        $currency = Site::getCurrencyList();

        $return = array();
        foreach (array_keys(Site::getCurrencyList()) as $currencyCode) {
            $return[$currencyCode] = Piwik::translate('Intl_Currency_' . $currencyCode) .
              ' (' . Piwik::translate('Intl_CurrencySymbol_' . $currencyCode) . ')';
        }

        asort($return);

        return $return;
    }

    /**
     * Returns the list of currency symbols
     * @see getCurrencyList()
     * @return array( currencyId => currencySymbol )
     */
    public function getCurrencySymbols()
    {
        $currencies = Site::getCurrencyList();
        return array_map(function ($a) {
            return $a[0];
        }, $currencies);
    }

    /**
     * Return true if Timezone support is enabled on server
     *
     * @return bool
     */
    public function isTimezoneSupportEnabled()
    {
        Piwik::checkUserHasSomeViewAccess();
        return SettingsServer::isTimezoneSupportEnabled();
    }

    /**
     * Returns the list of timezones supported.
     * Used for addSite and updateSite
     *
     * @return array of timezone strings
     */
    public function getTimezonesList()
    {
        if (!SettingsServer::isTimezoneSupportEnabled()) {
            return array('UTC' => $this->getTimezonesListUTCOffsets());
        }

        $countries = StaticContainer::get('Piwik\Intl\Data\Provider\RegionDataProvider')->getCountryList();

        $return = array();
        $continents = array();
        foreach ($countries as $countryCode => $continentCode) {
            $countryCode = strtoupper($countryCode);
            $timezones = DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, $countryCode);
            foreach ($timezones as $timezone) {
                if (!isset($continents[$continentCode])) {
                    $continents[$continentCode] = Piwik::translate('Intl_Continent_' . $continentCode);
                }
                $continent = $continents[$continentCode];

                $return[$continent][$timezone] = $this->getTimezoneName($timezone, $countryCode, count($timezones) > 1);
            }
        }

        // Sort by continent name and then by country name.
        ksort($return);
        foreach ($return as $continent => $countries) {
            asort($return[$continent]);
        }

        $return['UTC'] = $this->getTimezonesListUTCOffsets();
        return $return;
    }

    /**
     * Returns a user-friendly label for a timezone.
     * This is usually the country name of the timezone. For countries spanning multiple timezones,
     * a city/location name is added to avoid ambiguity.
     *
     * @param string $timezone a timezone, e.g. "Asia/Tokyo" or "America/Los_Angeles"
     * @param string $countryCode an upper-case country code (if not supplied, it will be looked up)
     * @param bool $multipleTimezonesInCountry whether there are multiple timezones in the country (if not supplied, it will be looked up)
     * @return string a timezone label, e.g. "Japan" or "United States - Los Angeles"
     */
    public function getTimezoneName($timezone, $countryCode = null, $multipleTimezonesInCountry = null)
    {
        if (substr($timezone, 0, 3) === 'UTC') {
            return Piwik::translate('SitesManager_Format_Utc', str_replace(array('.25', '.5', '.75'), array(':15', ':30', ':45'), substr($timezone, 3)));
        }

        if (!isset($countryCode)) {
            try {
                $zone = new DateTimeZone($timezone);
                $location = $zone->getLocation();
                if (isset($location['country_code']) && $location['country_code'] !== '??') {
                    $countryCode = $location['country_code'];
                }
            } catch (Exception $e) {
            }
        }

        if (!$countryCode) {
            $timezoneExploded = explode('/', $timezone);
            return str_replace('_', ' ', end($timezoneExploded));
        }

        if (!isset($multipleTimezonesInCountry)) {
            $timezonesInCountry = DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, $countryCode);
            $multipleTimezonesInCountry = (count($timezonesInCountry) > 1);
        }

        $return = Piwik::translate('Intl_Country_' . $countryCode);

        if ($multipleTimezonesInCountry) {
            $translationId = 'Intl_Timezone_' . str_replace(array('_', '/'), array('', '_'), $timezone);
            $city = Piwik::translate($translationId);

            // Fall back to English identifier, if translation is missing due to differences in tzdata in different PHP versions.
            if ($city === $translationId) {
                $timezoneExploded = explode('/', $timezone);
                $city = str_replace('_', ' ', end($timezoneExploded));
            }

            $return .= ' - ' . $city;
        }

        return $return;
    }

    private function getTimezonesListUTCOffsets()
    {
        // manually add the UTC offsets
        $GmtOffsets = array(-12, -11.5, -11, -10.5, -10, -9.5, -9, -8.5, -8, -7.5, -7, -6.5, -6, -5.5, -5, -4.5, -4, -3.5, -3, -2.5, -2, -1.5, -1, -0.5,
                            0, 0.5, 1, 1.5, 2, 2.5, 3, 3.5, 4, 4.5, 5, 5.5, 5.75, 6, 6.5, 7, 7.5, 8, 8.5, 8.75, 9, 9.5, 10, 10.5, 11, 11.5, 12, 12.75, 13, 13.75, 14);

        $return = array();
        foreach ($GmtOffsets as $offset) {
            $offset = Common::forceDotAsSeparatorForDecimalPoint($offset);

            if ($offset > 0) {
                $offset = '+' . $offset;
            } elseif ($offset == 0) {
                $offset = '';
            }
            $timezone = 'UTC' . $offset;
            $return[$timezone] = $this->getTimezoneName($timezone);
        }
        return $return;
    }

    /**
     * Returns the list of unique timezones from all configured sites.
     *
     * @return array ( string )
     */
    public function getUniqueSiteTimezones()
    {
        Piwik::checkUserHasSuperUserAccess();

        return $this->getModel()->getUniqueSiteTimezones();
    }

    /**
     * Remove the final slash in the URLs if found
     *
     * @param string $url
     * @return string the URL without the trailing slash
     */
    private function removeTrailingSlash($url)
    {
        // if there is a final slash, we take the URL without this slash (expected URL format)
        if (strlen($url) > 5
            && $url[strlen($url) - 1] == '/'
        ) {
            $url = substr($url, 0, strlen($url) - 1);
        }

        return $url;
    }

    /**
     * Tests if the URL is a valid URL
     *
     * @param string $url
     * @return bool
     */
    private function isValidUrl($url)
    {
        return UrlHelper::isLookLikeUrl($url);
    }

    /**
     * Tests if the IP is a valid IP, allowing wildcards, except in the first octet.
     * Wildcards can only be used from right to left, ie. 1.1.*.* is allowed, but 1.1.*.1 is not.
     *
     * @param string $ip IP address
     * @return bool
     */
    private function isValidIp($ip)
    {
        return IPUtils::getIPRangeBounds($ip) !== null;
    }

    /**
     * Check that the website name has a correct format.
     *
     * @param $siteName
     * @throws Exception
     */
    private function checkName($siteName)
    {
        if (empty($siteName)) {
            throw new Exception(Piwik::translate("SitesManager_ExceptionEmptyName"));
        }
    }

    public function renameGroup($oldGroupName, $newGroupName)
    {
        Piwik::checkUserHasSuperUserAccess();

        if ($oldGroupName == $newGroupName) {
            return true;
        }

        $sitesHavingOldGroup = $this->getSitesFromGroup($oldGroupName);

        foreach ($sitesHavingOldGroup as $site) {
            $this->updateSite($site['idsite'],
                              $siteName = null,
                              $urls = null,
                              $ecommerce = null,
                              $siteSearch = null,
                              $searchKeywordParameters = null,
                              $searchCategoryParameters = null,
                              $excludedIps = null,
                              $excludedQueryParameters = null,
                              $timezone = null,
                              $currency = null,
                              $newGroupName);
        }

        return true;
    }

    /**
     * Find websites matching the given pattern.
     *
     * Any website will be returned that matches the pattern in the name, URL or group.
     * To limit the number of returned sites you can either specify `filter_limit` as usual or `limit` which is
     * faster.
     *
     * @param string $pattern
     * @param int|false $limit
     * @return array
     */
    public function getPatternMatchSites($pattern, $limit = false)
    {
        $ids = $this->getSitesIdWithAtLeastViewAccess();
        if (empty($ids)) {
            return array();
        }

        $sites = $this->getModel()->getPatternMatchSites($ids, $pattern, $limit);

        foreach ($sites as &$site) {
            $this->enrichSite($site);
        }

        $sites = Site::setSitesFromArray($sites);

        return $sites;
    }

    /**
     * Returns the number of websites to display per page.
     *
     * For example this is used in the All Websites Dashboard, in the Website Selector etc. If multiple websites are
     * shown somewhere, one should request this method to detect how many websites should be shown per page when
     * using paging. To use paging is always recommended since some installations have thousands of websites.
     *
     * @return int
     */
    public function getNumWebsitesToDisplayPerPage()
    {
        Piwik::checkUserHasSomeViewAccess();

        return SettingsPiwik::getWebsitesCountToDisplay();
    }

}
