<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\TagManager\Template\Variable;

use Piwik\Common;
use Piwik\Piwik;
use Piwik\Settings\FieldConfig;
use Piwik\SettingsPiwik;
use Piwik\Site;
use Piwik\Tracker\TrackerCodeGenerator;
use Piwik\Validators\CharacterLength;
use Piwik\Validators\NotEmpty;
use Piwik\Validators\UrlLike;

class MatomoConfigurationVariable extends BaseVariable
{
    const ID = 'MatomoConfiguration';

    public function getId()
    {
        return self::ID;
    }

    public function getCategory()
    {
        return self::CATEGORY_OTHERS;
    }

    public function getIcon()
    {
        return 'plugins/TagManager/images/MatomoIcon.png';
    }

    public function hasAdvancedSettings()
    {
        return false;
    }

    public function getParameters()
    {
        $idSite = Common::getRequestVar('idSite', 0, 'int');

        $url = SettingsPiwik::getPiwikUrl();
        if (SettingsPiwik::isHttpsForced()) {
            $url = str_replace('http://', 'https://', $url);
        } else {
            $url = str_replace(array('http://', 'https://'), '//', $url);
        }

        $matomoUrl = $this->makeSetting('matomoUrl', $url, FieldConfig::TYPE_STRING, function (FieldConfig $field) {
            $field->title = Piwik::translate('TagManager_MatomoConfigurationMatomoUrlTitle');
            $field->customUiControlTemplateFile = self::FIELD_TEMPLATE_VARIABLE;
            $field->description = Piwik::translate('TagManager_MatomoConfigurationMatomoUrlDescription');
            $field->validators[] = new NotEmpty();
        });

        $trackerCodeGenerator = new TrackerCodeGenerator();
        $jsEndpoint = $trackerCodeGenerator->getJsTrackerEndpoint();
        $phpEndpoint = $trackerCodeGenerator->getPhpTrackerEndpoint();

        return array(
            $matomoUrl,
            $this->makeSetting('idSite', $idSite, FieldConfig::TYPE_STRING, function (FieldConfig $field) use ($matomoUrl, $url) {
                $field->title = Piwik::translate('TagManager_MatomoConfigurationMatomoIDSiteTitle');
                $field->customUiControlTemplateFile = self::FIELD_TEMPLATE_VARIABLE;
                $field->description = Piwik::translate('TagManager_MatomoConfigurationMatomoIDSiteDescription');;
                $field->validators[] = new NotEmpty();
                $field->validators[] = new CharacterLength(0, 500);
                $field->validate = function ($value) use ($matomoUrl, $url) {
                    if (is_numeric($value)) {
                        if ($matomoUrl->getValue() === $url) {
                            new Site($value);// we validate idSite when it points to this url
                        }
                        return; // valid... we do not validate idSite as it might point to different matomo...
                    }
                    $posBracket = strpos($value, '{{');
                    if ($posBracket === false || strpos($value, '}}', $posBracket) === false) {
                        throw new \Exception(Piwik::translate('TagManager_MatomoConfigurationMatomoIDSiteException'));
                    }
                };
            }),
            $this->makeSetting('enableLinkTracking', true, FieldConfig::TYPE_BOOL, function (FieldConfig $field) {
                $field->title = Piwik::translate('TagManager_MatomoConfigurationMatomoEnableLinkTrackingTitle');
                $field->description = Piwik::translate('TagManager_MatomoConfigurationMatomoEnableLinkTrackingDescription');
            }),
            $this->makeSetting('enableCrossDomainLinking', false, FieldConfig::TYPE_BOOL, function (FieldConfig $field) {
                $field->title = Piwik::translate('TagManager_MatomoConfigurationMatomoEnableCrossDomainLinkingTitle');
                $field->description = Piwik::translate('TagManager_MatomoConfigurationMatomoEnableCrossDomainLinkingDescription');
            }),
            $this->makeSetting('enableDoNotTrack', false, FieldConfig::TYPE_BOOL, function (FieldConfig $field) {
                $field->title = Piwik::translate('TagManager_MatomoConfigurationMatomoEnableDoNotTrackTitle');
                $field->description = Piwik::translate('TagManager_MatomoConfigurationMatomoEnableDoNotTrackDescription');
                $field->inlineHelp = Piwik::translate('TagManager_MatomoConfigurationMatomoEnableDoNotTrackInlineHelp', array('<strong>', '</strong>'));
            }),
            $this->makeSetting('enableJSErrorTracking', false, FieldConfig::TYPE_BOOL, function (FieldConfig $field) {
                $field->title = Piwik::translate('TagManager_MatomoConfigurationMatomoEnableJSErrorTrackingTitle');
                $field->description = Piwik::translate('TagManager_MatomoConfigurationMatomoEnableJSErrorTrackingDescription');
            }),
            $this->makeSetting('enableHeartBeatTimer', false, FieldConfig::TYPE_BOOL, function (FieldConfig $field) {
                $field->title = Piwik::translate('TagManager_MatomoConfigurationMatomoEnableHeartBeatTimerTitle');
                $field->description = Piwik::translate('TagManager_MatomoConfigurationMatomoEnableHeartBeatTimerDescription');
            }),
            $this->makeSetting('trackAllContentImpressions', false, FieldConfig::TYPE_BOOL, function (FieldConfig $field) {
                $field->title = Piwik::translate('TagManager_MatomoConfigurationMatomoTrackAllContentImpressionsTitle');
                $field->description = Piwik::translate('TagManager_MatomoConfigurationMatomoTrackAllContentImpressionsDescription');
            }),
            $this->makeSetting('trackVisibleContentImpressions', false, FieldConfig::TYPE_BOOL, function (FieldConfig $field) {
                $field->title = Piwik::translate('TagManager_MatomoConfigurationMatomoTrackVisibleContentImpressionsTitle');
                $field->description = Piwik::translate('TagManager_MatomoConfigurationMatomoTrackVisibleContentImpressionsDescription');
            }),
            $this->makeSetting('disableCookies', false, FieldConfig::TYPE_BOOL, function (FieldConfig $field) {
                $field->title = Piwik::translate('TagManager_MatomoConfigurationMatomoDisableCookiesTitle');
                $field->description = Piwik::translate('TagManager_MatomoConfigurationMatomoDisableCookiesDescription');
                $field->condition = '!requireConsent && !requireCookieConsent';
            }),
            $this->makeSetting('requireConsent', false, FieldConfig::TYPE_BOOL, function (FieldConfig $field) {
                $field->title = Piwik::translate('TagManager_MatomoConfigurationMatomoRequireConsentTitle');
                $field->description = Piwik::translate('TagManager_MatomoConfigurationMatomoRequireConsentDescription');
                $field->condition = '!requireCookieConsent && !disableCookies';
            }),
            $this->makeSetting('requireCookieConsent', false, FieldConfig::TYPE_BOOL, function (FieldConfig $field) {
                $field->title = Piwik::translate('TagManager_MatomoConfigurationMatomoRequireCookieConsentTitle');
                $field->description = Piwik::translate('TagManager_MatomoConfigurationMatomoRequireCookieConsentDescription');
                $field->condition = '!requireConsent && !disableCookies';
            }),
            $this->makeSetting('setSecureCookie', false, FieldConfig::TYPE_BOOL, function (FieldConfig $field) {
                $field->title = Piwik::translate('TagManager_MatomoConfigurationMatomoSetSecureCookieTitle');
                $field->description = Piwik::translate('TagManager_MatomoConfigurationMatomoSetSecureCookieDescription');
            }),
            $this->makeSetting('cookieDomain', '', FieldConfig::TYPE_STRING, function (FieldConfig $field) {
                $field->title = Piwik::translate('TagManager_MatomoConfigurationMatomoCookieDomainTitle');
                $field->inlineHelp = Piwik::translate('TagManager_MatomoConfigurationMatomoCookieDomainInlineHelp', array('<br><strong>', '</strong>'));
                $field->validators[] = new CharacterLength(0, 500);
                $field->customUiControlTemplateFile = self::FIELD_TEMPLATE_VARIABLE;
            }),
            $this->makeSetting('cookiePath', '', FieldConfig::TYPE_STRING, function (FieldConfig $field) {
                $field->title = Piwik::translate('TagManager_MatomoConfigurationMatomoCookiePathTitle');
                $field->description = Piwik::translate('TagManager_MatomoConfigurationMatomoCookiePathDescription');
                $field->validators[] = new CharacterLength(0, 500);
            }),
            $this->makeSetting('cookieSameSite', 'Lax', FieldConfig::TYPE_STRING, function (FieldConfig $field) {
                $field->title = Piwik::translate('TagManager_MatomoConfigurationMatomoCookieSameSiteTitle');
                $field->description = Piwik::translate('TagManager_MatomoConfigurationMatomoCookieSameSiteDescription');
                $field->uiControl = FieldConfig::UI_CONTROL_SINGLE_SELECT;
                $field->availableValues = array(
                    'Lax' => 'Lax',
                    'None' => 'None',
                    'Strict' => 'Strict',
                );
            }),
            $this->makeSetting('domains', array(), FieldConfig::TYPE_ARRAY, function (FieldConfig $field) {
                $field->title = Piwik::translate('TagManager_MatomoConfigurationMatomoDomainsTitle');
                $field->description = Piwik::translate('TagManager_MatomoConfigurationMatomoDomainsDescription');
                $field->validate = function ($value) {
                    if (empty($value)) {
                        return;
                    }
                    if (!is_array($value)) {
                        throw new \Exception(Piwik::translate('TagManager_MatomoConfigurationMatomoDomainsException'));
                    }
                };

                $field->transform = function ($value) {
                    if (empty($value) || !is_array($value)) {
                        return array();
                    }
                    $withValues = array();
                    foreach ($value as $domain) {
                        if (!empty($domain['domain'])) {
                            $withValues[] = $domain;
                        }
                    }

                    return $withValues;
                };

                $field->uiControl = FieldConfig::UI_CONTROL_MULTI_TUPLE;
                $field1 = new FieldConfig\MultiPair('Domain', 'domain', FieldConfig::UI_CONTROL_TEXT);
                $field1->customUiControlTemplateFile = self::FIELD_TEMPLATE_VARIABLE;
                $field->uiControlAttributes['field1'] = $field1->toArray();
            }),

            $this->makeSetting('alwaysUseSendBeacon', false, FieldConfig::TYPE_BOOL, function (FieldConfig $field) {
                $field->title = Piwik::translate('TagManager_MatomoConfigurationMatomoAlwaysUseSendBeaconTitle');
                $field->description = Piwik::translate('TagManager_MatomoConfigurationMatomoAlwaysUseSendBeaconDescription');
            }),
            $this->makeSetting('userId', '', FieldConfig::TYPE_STRING, function (FieldConfig $field) {
                $field->title = Piwik::translate('TagManager_MatomoConfigurationMatomoUserIdTitle');
                $field->description = Piwik::translate('TagManager_MatomoConfigurationMatomoUserIdDescription');
                $field->validators[] = new CharacterLength(0, 500);
                $field->customUiControlTemplateFile = self::FIELD_TEMPLATE_VARIABLE;
            }),
            $this->makeSetting('customDimensions', array(), FieldConfig::TYPE_ARRAY, function (FieldConfig $field) {
                $field->title = Piwik::translate('TagManager_MatomoConfigurationMatomoCustomDimensionsTitle');
                $field->description = Piwik::translate('TagManager_MatomoConfigurationMatomoCustomDimensionsDescription');
                $field->validate = function ($value) {
                    if (empty($value)) {
                        return;
                    }
                    if (!is_array($value)) {
                        throw new \Exception(Piwik::translate('TagManager_MatomoConfigurationMatomoCustomDimensionsException'));
                    }
                };

                $field->transform = function ($value) {
                    if (empty($value) || !is_array($value)) {
                        return array();
                    }
                    $withValues = array();
                    foreach ($value as $dim) {
                        if (!empty($dim['index']) && !empty($dim['value'])) {
                            $withValues[] = $dim;
                        }
                    }

                    return $withValues;
                };

                $field->uiControl = FieldConfig::UI_CONTROL_MULTI_TUPLE;
                $field1 = new FieldConfig\MultiPair('Index', 'index', FieldConfig::UI_CONTROL_TEXT);
                $field1->customUiControlTemplateFile = self::FIELD_TEMPLATE_VARIABLE;
                $field2 = new FieldConfig\MultiPair('Value', 'value', FieldConfig::UI_CONTROL_TEXT);
                $field2->customUiControlTemplateFile = self::FIELD_TEMPLATE_VARIABLE;
                $field->uiControlAttributes['field1'] = $field1->toArray();
                $field->uiControlAttributes['field2'] = $field2->toArray();
            }),
            $this->makeSetting('bundleTracker', true, FieldConfig::TYPE_BOOL, function (FieldConfig $field) {
                $field->title = Piwik::translate('TagManager_MatomoConfigurationMatomoBundleTrackerTitle');
                $field->uiControl = FieldConfig::UI_CONTROL_CHECKBOX;
                $field->description = Piwik::translate('TagManager_MatomoConfigurationMatomoBundleTrackerDescription');
            }),
            $this->makeSetting('registerAsDefaultTracker', true, FieldConfig::TYPE_BOOL, function (FieldConfig $field) {
                $field->title = Piwik::translate('TagManager_MatomoConfigurationMatomoRegisterAsDefaultTrackerTitle');
                $field->uiControl = FieldConfig::UI_CONTROL_CHECKBOX;
                $field->description = Piwik::translate('TagManager_MatomoConfigurationMatomoRegisterAsDefaultTrackerDescription');
            }),
            $this->makeSetting('jsEndpoint', $jsEndpoint, FieldConfig::TYPE_STRING, function (FieldConfig $field) {
                $field->title = Piwik::translate('TagManager_MatomoConfigurationMatomoJsEndpointTitle');
                $field->uiControl = FieldConfig::UI_CONTROL_SINGLE_SELECT;
                $field->availableValues = array(
                    'matomo.js' => 'matomo.js',
                    'piwik.js' => 'piwik.js',
                    'js/' => 'js/',
                    'js/tracker.php' => 'js/tracker.php',
                );

                $field->description = Piwik::translate('TagManager_MatomoConfigurationMatomoJsEndpointDescription');
            }),
            $this->makeSetting('trackingEndpoint', $phpEndpoint, FieldConfig::TYPE_STRING, function (FieldConfig $field) {
                $field->title = Piwik::translate('TagManager_MatomoConfigurationMatomoTrackingEndpointTitle');
                $field->uiControl = FieldConfig::UI_CONTROL_SINGLE_SELECT;
                $field->availableValues = array(
                    'matomo.php' => 'matomo.php',
                    'piwik.php' => 'piwik.php',
                    'js/' => 'js/',
                    'js/tracker.php' => 'js/tracker.php',
                );

                $field->description = Piwik::translate('TagManager_MatomoConfigurationMatomoTrackingEndpointDescription');
            }),
        );
    }

}
