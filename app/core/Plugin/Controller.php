<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugin;

use Exception;
use Piwik\Access;
use Piwik\API\Proxy;
use Piwik\API\Request;
use Piwik\Common;
use Piwik\Config as PiwikConfig;
use Piwik\Container\StaticContainer;
use Piwik\DataTable\Filter\SafeDecodeLabel;
use Piwik\Date;
use Piwik\Exception\NoPrivilegesException;
use Piwik\Exception\NoWebsiteFoundException;
use Piwik\FrontController;
use Piwik\Menu\MenuAdmin;
use Piwik\Menu\MenuTop;
use Piwik\NoAccessException;
use Piwik\Notification\Manager as NotificationManager;
use Piwik\Period\Month;
use Piwik\Period;
use Piwik\Period\PeriodValidator;
use Piwik\Period\Range;
use Piwik\Piwik;
use Piwik\Plugins\CoreAdminHome\CustomLogo;
use Piwik\Plugins\CoreVisualizations\Visualizations\JqplotGraph\Evolution;
use Piwik\Plugins\LanguagesManager\LanguagesManager;
use Piwik\SettingsPiwik;
use Piwik\Site;
use Piwik\Url;
use Piwik\Plugin;
use Piwik\View;
use Piwik\View\ViewInterface;
use Piwik\ViewDataTable\Factory as ViewDataTableFactory;

/**
 * Base class of all plugin Controllers.
 *
 * Plugins that wish to add display HTML should create a Controller that either
 * extends from this class or from {@link ControllerAdmin}. Every public method in
 * the controller will be exposed as a controller method and can be invoked via
 * an HTTP request.
 *
 * Learn more about Piwik's MVC system [here](/guides/mvc-in-piwik).
 *
 * ### Examples
 *
 * **Defining a controller**
 *
 *     class Controller extends \Piwik\Plugin\Controller
 *     {
 *         public function index()
 *         {
 *             $view = new View("@MyPlugin/index.twig");
 *             // ... setup view ...
 *             return $view->render();
 *         }
 *     }
 *
 * **Linking to a controller action**
 *
 *     <a href="?module=MyPlugin&action=index&idSite=1&period=day&date=2013-10-10">Link</a>
 *
 */
abstract class Controller
{
    /**
     * The plugin name, eg. `'Referrers'`.
     *
     * @var string
     * @api
     */
    protected $pluginName;

    /**
     * The value of the **date** query parameter.
     *
     * @var string
     * @api
     */
    protected $strDate;

    /**
     * The Date object created with ($strDate)[#strDate] or null if the requested date is a range.
     *
     * @var Date|null
     * @api
     */
    protected $date;

    /**
     * The value of the **idSite** query parameter.
     *
     * @var int
     * @api
     */
    protected $idSite;

    /**
     * The Site object created with {@link $idSite}.
     *
     * @var Site
     * @api
     */
    protected $site = null;

    /**
     * Constructor.
     *
     * @api
     */
    public function __construct()
    {
        $this->init();
    }

    protected function init()
    {
        $aPluginName = explode('\\', get_class($this));
        $this->pluginName = $aPluginName[2];

        $date = Common::getRequestVar('date', 'yesterday', 'string');
        try {
            $this->idSite = Common::getRequestVar('idSite', false, 'int');
            $this->site = new Site($this->idSite);
            $date = $this->getDateParameterInTimezone($date, $this->site->getTimezone());
            $this->setDate($date);
        } catch (Exception $e) {
            // the date looks like YYYY-MM-DD,YYYY-MM-DD or other format
            $this->date = null;
        }
    }

    /**
     * Helper method that converts `"today"` or `"yesterday"` to the specified timezone.
     * If the date is absolute, ie. YYYY-MM-DD, it will not be converted to the timezone.
     *
     * @param string $date `'today'`, `'yesterday'`, `'YYYY-MM-DD'`
     * @param string $timezone The timezone to use.
     * @return Date
     * @api
     */
    protected function getDateParameterInTimezone($date, $timezone)
    {
        $timezoneToUse = null;
        // if the requested date is not YYYY-MM-DD, we need to ensure
        //  it is relative to the website's timezone
        if (in_array($date, array('today', 'yesterday'))) {
            // today is at midnight; we really want to get the time now, so that
            // * if the website is UTC+12 and it is 5PM now in UTC, the calendar will allow to select the UTC "tomorrow"
            // * if the website is UTC-12 and it is 5AM now in UTC, the calendar will allow to select the UTC "yesterday"
            if ($date == 'today') {
                $date = 'now';
            } elseif ($date == 'yesterday') {
                $date = 'yesterdaySameTime';
            }
            $timezoneToUse = $timezone;
        }
        return Date::factory($date, $timezoneToUse);
    }

    /**
     * Sets the date to be used by all other methods in the controller.
     * If the date has to be modified, this method should be called just after
     * construction.
     *
     * @param Date $date The new Date.
     * @return void
     * @api
     */
    protected function setDate(Date $date)
    {
        $this->date = $date;
        $this->strDate = $date->toString();
    }

    /**
     * Returns values that are enabled for the parameter &period=
     * @return array eg. array('day', 'week', 'month', 'year', 'range')
     */
    protected static function getEnabledPeriodsInUI()
    {
        $periodValidator = new PeriodValidator();
        return $periodValidator->getPeriodsAllowedForUI();
    }

    /**
     * @return array
     */
    private static function getEnabledPeriodsNames()
    {
        $availablePeriods = self::getEnabledPeriodsInUI();
        $periodNames = array(
            'day'   => array(
                'singular' => Piwik::translate('Intl_PeriodDay'),
                'plural' => Piwik::translate('Intl_PeriodDays')
            ),
            'week'  => array(
                'singular' => Piwik::translate('Intl_PeriodWeek'),
                'plural' => Piwik::translate('Intl_PeriodWeeks')
            ),
            'month' => array(
                'singular' => Piwik::translate('Intl_PeriodMonth'),
                'plural' => Piwik::translate('Intl_PeriodMonths')
            ),
            'year'  => array(
                'singular' => Piwik::translate('Intl_PeriodYear'),
                'plural' => Piwik::translate('Intl_PeriodYears')
            ),
            // Note: plural is not used for date range
            'range' => array(
                'singular' => Piwik::translate('General_DateRangeInPeriodList'),
                'plural' => Piwik::translate('General_DateRangeInPeriodList')
            ),
        );

        $periodNames = array_intersect_key($periodNames, array_fill_keys($availablePeriods, true));
        return $periodNames;
    }

    /**
     * Returns the name of the default method that will be called
     * when visiting: index.php?module=PluginName without the action parameter.
     *
     * @return string
     * @api
     */
    public function getDefaultAction()
    {
        return 'index';
    }

    /**
     * A helper method that renders a view either to the screen or to a string.
     *
     * @param ViewInterface $view The view to render.
     * @return string|void
     */
    protected function renderView(ViewInterface $view)
    {
        return $view->render();
    }

    /**
     * Assigns the given variables to the template and renders it.
     *
     * Example:
     *
     *     public function myControllerAction () {
     *        return $this->renderTemplate('index', array(
     *            'answerToLife' => '42'
     *        ));
     *     }
     *
     * This will render the 'index.twig' file within the plugin templates folder and assign the view variable
     * `answerToLife` to `42`.
     *
     * @param string $template   The name of the template file. If only a name is given it will automatically use
     *                           the template within the plugin folder. For instance 'myTemplate' will result in
     *                           '@$pluginName/myTemplate.twig'. Alternatively you can include the full path:
     *                           '@anyOtherFolder/otherTemplate'. The trailing '.twig' is not needed.
     * @param array $variables   For instance array('myViewVar' => 'myValue'). In template you can use {{ myViewVar }}
     * @return string
     * @since 2.5.0
     * @api
     */
    protected function renderTemplate($template, array $variables = [])
    {
        return $this->renderTemplateAs($template, $variables);
    }

    /**
     * @see {self::renderTemplate()}
     *
     * @param $template
     * @param array $variables
     * @param string|null $viewType 'basic' or 'admin'. If null, determined based on the controller instance type.
     * @return string
     * @throws Exception
     */
    protected function renderTemplateAs($template, array $variables = array(), $viewType = null)
    {
        if (false === strpos($template, '@') || false === strpos($template, '/')) {
            $template = '@' . $this->pluginName . '/' . $template;
        }

        $view = new View($template);

        $this->checkViewType($viewType);

        if (empty($viewType)) {
            $viewType = $this instanceof ControllerAdmin ? 'admin' : 'basic';
        }

        // alternatively we could check whether the templates extends either admin.twig or dashboard.twig and based on
        // that call the correct method. This will be needed once we unify Controller and ControllerAdmin see
        // https://github.com/piwik/piwik/issues/6151
        if ($this instanceof ControllerAdmin && $viewType == 'admin') {
            $this->setBasicVariablesViewAs($view, $viewType);
        } elseif (empty($this->site) || empty($this->idSite)) {
            $this->setBasicVariablesViewAs($view, $viewType);
        } else {
            $this->setGeneralVariablesViewAs($view, $viewType);
        }

        foreach ($variables as $key => $value) {
            $view->$key = $value;
        }

        if (isset($view->siteName)) {
            $view->siteNameDecoded = Common::unsanitizeInputValue($view->siteName);
        }

        return $view->render();
    }

    /**
     * Convenience method that creates and renders a ViewDataTable for a API method.
     *
     * @param string|\Piwik\Plugin\Report $apiAction The name of the API action (eg, `'getResolution'`) or
     *                                      an instance of an report.
     * @param bool $controllerAction The name of the Controller action name  that is rendering the report. Defaults
     *                               to the `$apiAction`.
     * @param bool $fetch If `true`, the rendered string is returned, if `false` it is `echo`'d.
     * @throws \Exception if `$pluginName` is not an existing plugin or if `$apiAction` is not an
     *                    existing method of the plugin's API.
     * @return string|void See `$fetch`.
     * @api
     */
    protected function renderReport($apiAction, $controllerAction = false)
    {
        if (empty($controllerAction) && is_string($apiAction)) {
            $report = ReportsProvider::factory($this->pluginName, $apiAction);

            if (!empty($report)) {
                $apiAction = $report;
            }
        }

        if ($apiAction instanceof Report) {
            $this->checkSitePermission();
            $apiAction->checkIsEnabled();

            return $apiAction->render();
        }

        $pluginName = $this->pluginName;

        /** @var Proxy $apiProxy */
        $apiProxy = Proxy::getInstance();

        if (!$apiProxy->isExistingApiAction($pluginName, $apiAction)) {
            throw new \Exception("Invalid action name '$apiAction' for '$pluginName' plugin.");
        }

        $apiAction = $apiProxy->buildApiActionName($pluginName, $apiAction);

        if ($controllerAction !== false) {
            $controllerAction = $pluginName . '.' . $controllerAction;
        }

        $view      = ViewDataTableFactory::build(null, $apiAction, $controllerAction);
        $rendered  = $view->render();

        return $rendered;
    }

    /**
     * Returns a ViewDataTable object that will render a jqPlot evolution graph
     * for the last30 days/weeks/etc. of the current period, relative to the current date.
     *
     * @param string $currentModuleName The name of the current plugin.
     * @param string $currentControllerAction The name of the action that renders the desired
     *                                        report.
     * @param string $apiMethod The API method that the ViewDataTable will use to get
     *                          graph data.
     * @return ViewDataTable
     * @api
     */
    protected function getLastUnitGraph($currentModuleName, $currentControllerAction, $apiMethod)
    {
        $view = ViewDataTableFactory::build(
            Evolution::ID, $apiMethod, $currentModuleName . '.' . $currentControllerAction, $forceDefault = true);
        $view->config->show_goals = false;
        return $view;
    }

    /**
     * Same as {@link getLastUnitGraph()}, but will set some properties of the ViewDataTable
     * object based on the arguments supplied.
     *
     * @param string $currentModuleName The name of the current plugin.
     * @param string $currentControllerAction The name of the action that renders the desired
     *                                        report.
     * @param array $columnsToDisplay The value to use for the ViewDataTable's columns_to_display config
     *                                property.
     * @param array $selectableColumns The value to use for the ViewDataTable's selectable_columns config
     *                                 property.
     * @param bool|string $reportDocumentation The value to use for the ViewDataTable's documentation config
     *                                         property.
     * @param string $apiMethod The API method that the ViewDataTable will use to get graph data.
     * @return ViewDataTable
     * @api
     */
    protected function getLastUnitGraphAcrossPlugins($currentModuleName, $currentControllerAction, $columnsToDisplay = false,
                                                     $selectableColumns = array(), $reportDocumentation = false,
                                                     $apiMethod = 'API.get')
    {
        // load translations from meta data
        $idSite = Common::getRequestVar('idSite');
        $period = Common::getRequestVar('period');
        $date = Common::getRequestVar('date');
        $meta = \Piwik\Plugins\API\API::getInstance()->getReportMetadata($idSite, $period, $date);

        $columns = array_merge($columnsToDisplay ? $columnsToDisplay : array(), $selectableColumns);
        $translations = array_combine($columns, $columns);
        foreach ($meta as $reportMeta) {
            if ($reportMeta['action'] == 'get' && !isset($reportMeta['parameters'])) {
                foreach ($columns as $column) {
                    if (isset($reportMeta['metrics'][$column])) {
                        $translations[$column] = $reportMeta['metrics'][$column];
                    }
                }
            }
        }

        // initialize the graph and load the data
        $view = $this->getLastUnitGraph($currentModuleName, $currentControllerAction, $apiMethod);

        if ($columnsToDisplay !== false) {
            $view->config->columns_to_display = $columnsToDisplay;
        }

        if (property_exists($view->config, 'selectable_columns')) {
            $view->config->selectable_columns = array_merge($view->config->selectable_columns ? : array(), $selectableColumns);
        }

        $view->config->translations += $translations;

        if ($reportDocumentation) {
            $view->config->documentation = $reportDocumentation;
        }

        return $view;
    }

    /**
     * Returns the array of new processed parameters once the parameters are applied.
     * For example: if you set range=last30 and date=2008-03-10,
     *  the date element of the returned array will be "2008-02-10,2008-03-10"
     *
     * Parameters you can set:
     * - range: last30, previous10, etc.
     * - date: YYYY-MM-DD, today, yesterday
     * - period: day, week, month, year
     *
     * @param array $paramsToSet array( 'date' => 'last50', 'viewDataTable' =>'sparkline' )
     * @throws \Piwik\NoAccessException
     * @return array
     */
    protected function getGraphParamsModified($paramsToSet = array())
    {
        if (!isset($paramsToSet['period'])) {
            $period = Common::getRequestVar('period');
        } else {
            $period = $paramsToSet['period'];
        }
        if ($period == 'range') {
            return $paramsToSet;
        }
        if (!isset($paramsToSet['range'])) {
            $range = 'last30';
        } else {
            $range = $paramsToSet['range'];
        }

        if (!isset($paramsToSet['date'])) {
            $endDate = $this->strDate;
        } else {
            $endDate = $paramsToSet['date'];
        }

        if (is_null($this->site)) {
            throw new NoAccessException("Website not initialized, check that you are logged in and/or using the correct token_auth.");
        }
        $paramDate = Range::getRelativeToEndDate($period, $range, $endDate, $this->site);

        $params = array_merge($paramsToSet, array('date' => $paramDate));
        return $params;
    }

    /**
     * Returns a numeric value from the API.
     * Works only for API methods that originally returns numeric values (there is no cast here)
     *
     * @param string $methodToCall Name of method to call, eg. Referrers.getNumberOfDistinctSearchEngines
     * @param bool|string $date A custom date to use when getting the value. If false, the 'date' query
     *                                          parameter is used.
     *
     * @return int|float
     */
    protected function getNumericValue($methodToCall, $date = false)
    {
        $params = $date === false ? array() : array('date' => $date);

        $return = Request::processRequest($methodToCall, $params);
        $columns = $return->getFirstRow()->getColumns();
        return reset($columns);
    }

    /**
     * Returns a URL to a sparkline image for a report served by the current plugin.
     *
     * The result of this URL should be used with the [sparkline()](/api-reference/Piwik/View#twig) twig function.
     *
     * The current site ID and period will be used.
     *
     * @param string $action Method name of the controller that serves the report.
     * @param array $customParameters The array of query parameter name/value pairs that
     *                                should be set in result URL.
     * @return string The generated URL.
     * @api
     */
    protected function getUrlSparkline($action, $customParameters = array())
    {
        $params = $this->getGraphParamsModified(
            array('viewDataTable' => 'sparkline',
                  'action'        => $action,
                  'module'        => $this->pluginName)
            + $customParameters
        );
        // convert array values to comma separated
        foreach ($params as &$value) {
            if (is_array($value)) {
                $value = rawurlencode(implode(',', $value));
            }
        }
        $url = Url::getCurrentQueryStringWithParametersModified($params);
        return $url;
    }

    /**
     * Sets the first date available in the period selector's calendar.
     *
     * @param Date $minDate The min date.
     * @param View $view The view that contains the period selector.
     * @api
     */
    protected function setMinDateView(Date $minDate, $view)
    {
        $view->minDateYear = $minDate->toString('Y');
        $view->minDateMonth = $minDate->toString('m');
        $view->minDateDay = $minDate->toString('d');
    }

    /**
     * Sets the last date available in the period selector's calendar. Usually this is just the "today" date
     * for a site (which varies based on the timezone of a site).
     *
     * @param Date $maxDate The max date.
     * @param View $view The view that contains the period selector.
     * @api
     */
    protected function setMaxDateView(Date $maxDate, $view)
    {
        $view->maxDateYear = $maxDate->toString('Y');
        $view->maxDateMonth = $maxDate->toString('m');
        $view->maxDateDay = $maxDate->toString('d');
    }

    /**
     * Assigns variables to {@link Piwik\View} instances that display an entire page.
     *
     * The following variables assigned:
     *
     * **date** - The value of the **date** query parameter.
     * **idSite** - The value of the **idSite** query parameter.
     * **rawDate** - The value of the **date** query parameter.
     * **prettyDate** - A pretty string description of the current period.
     * **siteName** - The current site's name.
     * **siteMainUrl** - The URL of the current site.
     * **startDate** - The start date of the current period. A {@link Piwik\Date} instance.
     * **endDate** - The end date of the current period. A {@link Piwik\Date} instance.
     * **language** - The current language's language code.
     * **config_action_url_category_delimiter** - The value of the `[General] action_url_category_delimiter`
     *                                            INI config option.
     * **topMenu** - The result of `MenuTop::getInstance()->getMenu()`.
     *
     * As well as the variables set by {@link setPeriodVariablesView()}.
     *
     * Will exit on error.
     *
     * @param View $view
     * @param string|null $viewType 'basic' or 'admin'. If null, set based on the type of controller.
     * @return void
     * @api
     */
    protected function setGeneralVariablesView($view)
    {
        $this->setGeneralVariablesViewAs($view, $viewType = null);
    }

    protected function setGeneralVariablesViewAs($view, $viewType)
    {
        $this->checkViewType($viewType);

        if ($viewType === null) {
            $viewType = $this instanceof ControllerAdmin ? 'admin' : 'basic';
        }

        $view->idSite = $this->idSite;
        $this->checkSitePermission();
        $this->setPeriodVariablesView($view);

        $view->siteName = $this->site->getName();
        $view->siteMainUrl = $this->site->getMainUrl();

        $siteTimezone = $this->site->getTimezone();

        $datetimeMinDate = $this->site->getCreationDate()->getDatetime();
        $minDate = Date::factory($datetimeMinDate, $siteTimezone);
        $this->setMinDateView($minDate, $view);

        $maxDate = Date::factory('now', $siteTimezone);
        $this->setMaxDateView($maxDate, $view);

        $rawDate = Common::getRequestVar('date');
        Period::checkDateFormat($rawDate);

        $periodStr = Common::getRequestVar('period');

        if ($periodStr != 'range') {
            $date      = Date::factory($this->strDate);
            $validDate = $this->getValidDate($date, $minDate, $maxDate);
            $period    = Period\Factory::build($periodStr, $validDate);

            if ($date->toString() !== $validDate->toString()) {
                // we to not always change date since it could convert a strDate "today" to "YYYY-MM-DD"
                // only change $this->strDate if it was not valid before
                $this->setDate($validDate);
            }
        } else {
            $period = new Range($periodStr, $rawDate, $siteTimezone);
        }

        // Setting current period start & end dates, for pre-setting the calendar when "Date Range" is selected
        $dateStart = $period->getDateStart();
        $dateStart = $this->getValidDate($dateStart, $minDate, $maxDate);

        $dateEnd   = $period->getDateEnd();
        $dateEnd   = $this->getValidDate($dateEnd, $minDate, $maxDate);

        if ($periodStr == 'range') {
            // make sure we actually display the correct calendar pretty date
            $newRawDate = $dateStart->toString() . ',' . $dateEnd->toString();
            $period = new Range($periodStr, $newRawDate, $siteTimezone);
        }

        $view->date = $this->strDate;
        $view->prettyDate = self::getCalendarPrettyDate($period);
        // prettyDateLong is not used by core, leaving in case plugins may be using it
        $view->prettyDateLong = $period->getLocalizedLongString();
        $view->rawDate = $rawDate;
        $view->startDate = $dateStart;
        $view->endDate = $dateEnd;

        $timezoneOffsetInSeconds = Date::getUtcOffset($siteTimezone);
        $view->timezoneOffset = $timezoneOffsetInSeconds;

        $language = LanguagesManager::getLanguageForSession();
        $view->language = !empty($language) ? $language : LanguagesManager::getLanguageCodeForCurrentUser();

        $this->setBasicVariablesViewAs($view, $viewType);

        $view->topMenu = MenuTop::getInstance()->getMenu();
        $view->adminMenu = MenuAdmin::getInstance()->getMenu();

        $notifications = $view->notifications;
        if (empty($notifications)) {
            $view->notifications = NotificationManager::getAllNotificationsToDisplay();
            NotificationManager::cancelAllNonPersistent();
        }
    }

    private function getValidDate(Date $date, Date $minDate, Date $maxDate)
    {
        if ($date->isEarlier($minDate)) {
            $date = $minDate;
        }

        if ($date->isLater($maxDate)) {
            $date = $maxDate;
        }

        return $date;
    }

    /**
     * Needed when a controller extends ControllerAdmin but you don't want to call the controller admin basic variables
     * view. Solves a problem when a controller has regular controller and admin controller views.
     * @param View $view
     */
    protected function setBasicVariablesNoneAdminView($view)
    {
        $view->clientSideConfig = PiwikConfig::getInstance()->getClientSideOptions();
        $view->isSuperUser = Access::getInstance()->hasSuperUserAccess();
        $view->hasSomeAdminAccess = Piwik::isUserHasSomeAdminAccess();
        $view->hasSomeViewAccess  = Piwik::isUserHasSomeViewAccess();
        $view->isUserIsAnonymous  = Piwik::isUserIsAnonymous();
        $view->hasSuperUserAccess = Piwik::hasUserSuperUserAccess();

        if (!Piwik::isUserIsAnonymous()) {
            $view->emailSuperUser = implode(',', Piwik::getAllSuperUserAccessEmailAddresses());
        }

        $capabilities = array();
        if ($this->idSite && $this->site) {
            $capabilityProvider = StaticContainer::get(Access\CapabilitiesProvider::class);
            foreach ($capabilityProvider->getAllCapabilities() as $capability) {
                if (Piwik::isUserHasCapability($this->idSite, $capability->getId())) {
                    $capabilities[] = $capability->getId();
                }
            }
        }

        $view->userCapabilities = $capabilities;

        $this->addCustomLogoInfo($view);

        $view->logoHeader = \Piwik\Plugins\API\API::getInstance()->getHeaderLogoUrl();
        $view->logoLarge = \Piwik\Plugins\API\API::getInstance()->getLogoUrl();
        $view->logoSVG = \Piwik\Plugins\API\API::getInstance()->getSVGLogoUrl();
        $view->hasSVGLogo = \Piwik\Plugins\API\API::getInstance()->hasSVGLogo();
        $view->superUserEmails = implode(',', Piwik::getAllSuperUserAccessEmailAddresses());
        $view->themeStyles = ThemeStyles::get();

        $general = PiwikConfig::getInstance()->General;
        $view->enableFrames = $general['enable_framed_pages']
            || (isset($general['enable_framed_logins']) && $general['enable_framed_logins']);
        $embeddedAsIframe = (Common::getRequestVar('module', '', 'string') == 'Widgetize');
        if (!$view->enableFrames && !$embeddedAsIframe) {
            $view->setXFrameOptions('sameorigin');
        }

        $pluginManager = Plugin\Manager::getInstance();
        $view->relativePluginWebDirs = (object) $pluginManager->getWebRootDirectoriesForCustomPluginDirs();
        $view->isMultiSitesEnabled = Manager::getInstance()->isPluginActivated('MultiSites');

        self::setHostValidationVariablesView($view);
    }

    /**
     * Assigns a set of generally useful variables to a {@link Piwik\View} instance.
     *
     * The following variables assigned:
     *
     * **isSuperUser** - True if the current user is the Super User, false if otherwise.
     * **hasSomeAdminAccess** - True if the current user has admin access to at least one site,
     *                          false if otherwise.
     * **isCustomLogo** - The value of the `branding_use_custom_logo` option.
     * **logoHeader** - The header logo URL to use.
     * **logoLarge** - The large logo URL to use.
     * **logoSVG** - The SVG logo URL to use.
     * **hasSVGLogo** - True if there is a SVG logo, false if otherwise.
     * **enableFrames** - The value of the `[General] enable_framed_pages` INI config option. If
     *                    true, {@link Piwik\View::setXFrameOptions()} is called on the view.
     *
     * Also calls {@link setHostValidationVariablesView()}.
     *
     * @param View $view
     * @param string $viewType 'basic' or 'admin'. Used by ControllerAdmin.
     * @api
     */
    protected function setBasicVariablesView($view)
    {
        $this->setBasicVariablesViewAs($view);
    }

    protected function setBasicVariablesViewAs($view, $viewType = null)
    {
        $this->checkViewType($viewType); // param is not used here, but the check can be useful for a developer

        $this->setBasicVariablesNoneAdminView($view);
    }

    protected function addCustomLogoInfo($view)
    {
        $customLogo = new CustomLogo();
        $view->isCustomLogo  = $customLogo->isEnabled();
        $view->customFavicon = $customLogo->getPathUserFavicon();
    }

    /**
     * Checks if the current host is valid and sets variables on the given view, including:
     *
     * - **isValidHost** - true if host is valid, false if otherwise
     * - **invalidHostMessage** - message to display if host is invalid (only set if host is invalid)
     * - **invalidHost** - the invalid hostname (only set if host is invalid)
     * - **mailLinkStart** - the open tag of a link to email the Super User of this problem (only set
     *                       if host is invalid)
     *
     * @param View $view
     * @api
     */
    public static function setHostValidationVariablesView($view)
    {
        // check if host is valid
        $view->isValidHost = Url::isValidHost();
        if (!$view->isValidHost) {
            // invalid host, so display warning to user
            $validHosts = Url::getTrustedHostsFromConfig();
            $validHost = $validHosts[0];
            $invalidHost = Common::sanitizeInputValue($_SERVER['HTTP_HOST']);

            $emailSubject = rawurlencode(Piwik::translate('CoreHome_InjectedHostEmailSubject', $invalidHost));
            $emailBody = rawurlencode(Piwik::translate('CoreHome_InjectedHostEmailBody'));
            $superUserEmail = implode(',', Piwik::getAllSuperUserAccessEmailAddresses());

            $mailToUrl = "mailto:$superUserEmail?subject=$emailSubject&body=$emailBody";
            $mailLinkStart = "<a href=\"$mailToUrl\">";

            $invalidUrl = Url::getCurrentUrlWithoutQueryString($checkIfTrusted = false);
            $validUrl = Url::getCurrentScheme() . '://' . $validHost
                . Url::getCurrentScriptName();
            $invalidUrl = Common::sanitizeInputValue($invalidUrl);
            $validUrl = Common::sanitizeInputValue($validUrl);

            $changeTrustedHostsUrl = "index.php"
                . Url::getCurrentQueryStringWithParametersModified(array(
                                                                        'module' => 'CoreAdminHome',
                                                                        'action' => 'generalSettings'
                                                                   ))
                . "#trustedHostsSection";

            $warningStart = Piwik::translate('CoreHome_InjectedHostWarningIntro', array(
                                                                                      '<strong>' . $invalidUrl . '</strong>',
                                                                                      '<strong>' . $validUrl . '</strong>'
                                                                                 )) . ' <br/>';

            if (Piwik::hasUserSuperUserAccess()) {
                $view->invalidHostMessage = $warningStart . ' '
                    . Piwik::translate('CoreHome_InjectedHostSuperUserWarning', array(
                                                                                    "<a href=\"$changeTrustedHostsUrl\">",
                                                                                    $invalidHost,
                                                                                    '</a>',
                                                                                    "<br/><a href=\"$validUrl\">",
                                                                                    $validHost,
                                                                                    '</a>'
                                                                               ));
            } elseif (Piwik::isUserIsAnonymous()) {
                $view->invalidHostMessage = $warningStart . ' '
                    . Piwik::translate('CoreHome_InjectedHostNonSuperUserWarning', array(
                        "<br/><a href=\"$validUrl\">",
                        '</a>',
                        '<span style="display:none">',
                        '</span>'
                    ));
            } else {
                $view->invalidHostMessage = $warningStart . ' '
                    . Piwik::translate('CoreHome_InjectedHostNonSuperUserWarning', array(
                                                                                       "<br/><a href=\"$validUrl\">",
                                                                                       '</a>',
                                                                                       $mailLinkStart,
                                                                                       '</a>'
                                                                                  ));
            }
            $view->invalidHostMessageHowToFix = '<p><b>How do I fix this problem and how do I login again?</b><br/> The Matomo Super User can manually edit the file piwik/config/config.ini.php
						and add the following lines: <pre>[General]' . "\n" . 'trusted_hosts[] = "' . $invalidHost . '"</pre>After making the change, you will be able to login again.</p>
						<p>You may also <i>disable this security feature (not recommended)</i>. To do so edit config/config.ini.php and add:
						<pre>[General]' . "\n" . 'enable_trusted_host_check=0</pre>';

            $view->invalidHost = $invalidHost; // for UserSettings warning
            $view->invalidHostMailLinkStart = $mailLinkStart;
        }
    }

    /**
     * Sets general period variables on a view, including:
     *
     * - **displayUniqueVisitors** - Whether unique visitors should be displayed for the current
     *                               period.
     * - **period** - The value of the **period** query parameter.
     * - **otherPeriods** - `array('day', 'week', 'month', 'year', 'range')`
     * - **periodsNames** - List of available periods mapped to their singular and plural translations.
     *
     * @param View $view
     * @throws Exception if the current period is invalid.
     * @api
     */
    public static function setPeriodVariablesView($view)
    {
        if (isset($view->period)) {
            return;
        }

        $periodValidator = new PeriodValidator();

        $currentPeriod = Common::getRequestVar('period');
        $view->displayUniqueVisitors = SettingsPiwik::isUniqueVisitorsEnabled($currentPeriod);
        $availablePeriods = $periodValidator->getPeriodsAllowedForUI();

        if (! $periodValidator->isPeriodAllowedForUI($currentPeriod)) {
            throw new Exception("Period must be one of: " . implode(", ", $availablePeriods));
        }

        $found = array_search($currentPeriod, $availablePeriods);
        unset($availablePeriods[$found]);

        $view->period = $currentPeriod;
        $view->otherPeriods = $availablePeriods;
        $view->enabledPeriods = self::getEnabledPeriodsInUI();
        $view->periodsNames = self::getEnabledPeriodsNames();
    }

    /**
     * Helper method used to redirect the current HTTP request to another module/action.
     *
     * This function will exit immediately after executing.
     *
     * @param string $moduleToRedirect The plugin to redirect to, eg. `"MultiSites"`.
     * @param string $actionToRedirect Action, eg. `"index"`.
     * @param int|null $websiteId The new idSite query parameter, eg, `1`.
     * @param string|null $defaultPeriod The new period query parameter, eg, `'day'`.
     * @param string|null $defaultDate The new date query parameter, eg, `'today'`.
     * @param array $parameters Other query parameters to append to the URL.
     * @api
     */
    public function redirectToIndex($moduleToRedirect, $actionToRedirect, $websiteId = null, $defaultPeriod = null,
                                    $defaultDate = null, $parameters = array())
    {
        try {
            $this->doRedirectToUrl($moduleToRedirect, $actionToRedirect, $websiteId, $defaultPeriod, $defaultDate, $parameters);
        } catch (Exception $e) {
            // no website ID to default to, so could not redirect
        }

        if (Piwik::hasUserSuperUserAccess()) {
            $siteTableName = Common::prefixTable('site');
            $message = "Error: no website was found in this Matomo installation.
			<br />Check the table '$siteTableName' in your database, it should contain your Matomo websites.";

            $ex = new NoWebsiteFoundException($message);
            $ex->setIsHtmlMessage();

            throw $ex;
        }

        if (!Piwik::isUserIsAnonymous()) {
            $currentLogin = Piwik::getCurrentUserLogin();
            $emails = implode(',', Piwik::getAllSuperUserAccessEmailAddresses());
            $errorMessage  = sprintf(Piwik::translate('CoreHome_NoPrivilegesAskPiwikAdmin'), $currentLogin, "<br/><a href='mailto:" . $emails . "?subject=Access to Matomo for user $currentLogin'>", "</a>");
            $errorMessage .= "<br /><br />&nbsp;&nbsp;&nbsp;<b><a href='index.php?module=" . Piwik::getLoginPluginName() . "&amp;action=logout'>&rsaquo; " . Piwik::translate('General_Logout') . "</a></b><br />";

            $ex = new NoPrivilegesException($errorMessage);
            $ex->setIsHtmlMessage();

            throw $ex;
        }

        echo FrontController::getInstance()->dispatch(Piwik::getLoginPluginName(), false);
        exit;
    }


    /**
     * Checks that the token_auth in the URL matches the currently logged-in user's token_auth.
     *
     * This is a protection against CSRF and should be used in all controller
     * methods that modify Piwik or any user settings.
     *
     * If called from JavaScript by using the `ajaxHelper` you have to call `ajaxHelper.withTokenInUrl();` before
     * `ajaxHandler.send();` to send the token along with the request.
     *
     * **The token_auth should never appear in the browser's address bar.**
     *
     * @throws \Piwik\NoAccessException If the token doesn't match.
     * @api
     */
    protected function checkTokenInUrl()
    {
        $tokenRequest = Common::getRequestVar('token_auth', false);
        $tokenUser = Piwik::getCurrentUserTokenAuth();

        if (empty($tokenRequest) && empty($tokenUser)) {
            return; // UI tests
        }

        if ($tokenRequest !== $tokenUser) {
            throw new NoAccessException(Piwik::translate('General_ExceptionInvalidToken'));
        }
    }

    /**
     * Returns a prettified date string for use in period selector widget.
     *
     * @param Period $period The period to return a pretty string for.
     * @return string
     * @api
     */
    public static function getCalendarPrettyDate($period)
    {
        if ($period instanceof Month) {
            // show month name when period is for a month

            return $period->getLocalizedLongString();
        } else {
            return $period->getPrettyString();
        }
    }

    /**
     * Returns the pretty date representation
     *
     * @param $date string
     * @param $period string
     * @return string Pretty date
     */
    public static function getPrettyDate($date, $period)
    {
        return self::getCalendarPrettyDate(Period\Factory::build($period, Date::factory($date)));
    }

    protected function checkSitePermission()
    {
        if (!empty($this->idSite)) {
            Access::getInstance()->checkUserHasViewAccess($this->idSite);
        } elseif (empty($this->site) || empty($this->idSite)) {
            throw new Exception("The requested website idSite is not found in the request, or is invalid.
				Please check that you are logged in Matomo and have permission to access the specified website.");
        }
    }

    /**
     * @param $moduleToRedirect
     * @param $actionToRedirect
     * @param $websiteId
     * @param $defaultPeriod
     * @param $defaultDate
     * @param $parameters
     * @throws Exception
     */
    private function doRedirectToUrl($moduleToRedirect, $actionToRedirect, $websiteId, $defaultPeriod, $defaultDate, $parameters)
    {
        $menu = new Menu();

        $parameters = array_merge(
            $menu->urlForDefaultUserParams($websiteId, $defaultPeriod, $defaultDate),
            $parameters
        );
        $queryParams = !empty($parameters) ? '&' . Url::getQueryStringFromParameters($parameters) : '';
        $url = "index.php?module=%s&action=%s";
        $url = sprintf($url, $moduleToRedirect, $actionToRedirect);
        $url = $url . $queryParams;
        Url::redirectToUrl($url);
    }

    private function checkViewType($viewType)
    {
        if ($viewType == 'admin' && !($this instanceof ControllerAdmin)) {
            throw new Exception("'admin' view type is only allowed with ControllerAdmin class.");
        }
    }
}

