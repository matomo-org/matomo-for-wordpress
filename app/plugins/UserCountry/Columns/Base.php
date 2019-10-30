<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\UserCountry\Columns;

use Piwik\Common;
use Piwik\Exception\InvalidRequestParameterException;
use Piwik\Network\IPUtils;
use Piwik\Plugin\Dimension\VisitDimension;
use Piwik\Plugins\UserCountry\VisitorGeolocator;
use Piwik\Plugins\PrivacyManager\Config as PrivacyManagerConfig;
use Piwik\Tracker\Visitor;
use Piwik\Tracker\Request;

abstract class Base extends VisitDimension
{
    /**
     * @var VisitorGeolocator
     */
    private $visitorGeolocator;

    protected function getUrlOverrideValueIfAllowed($urlParamToOverride, Request $request)
    {
        return self::getValueFromUrlParamsIfAllowed($urlParamToOverride, $request);
    }

    public static function getValueFromUrlParamsIfAllowed($urlParamToOverride, Request $request)
    {
        $value = Common::getRequestVar($urlParamToOverride, false, 'string', $request->getParams());

        if (!empty($value)) {
            if (!$request->isAuthenticated()) {
                Common::printDebug("WARN: Tracker API '$urlParamToOverride' was used with invalid token_auth");
                throw new InvalidRequestParameterException("Tracker API '$urlParamToOverride' was used, requires valid token_auth");
            }
            return $value;
        }

        return false;
    }

    public function getRequiredVisitFields()
    {
        return array('location_ip', 'location_browser_lang');
    }

    protected function getLocationDetail($userInfo, $locationKey)
    {
        $useLocationCache = empty($GLOBALS['PIWIK_TRACKER_LOCAL_TRACKING']);
        $location = $this->getVisitorGeolocator()->getLocation($userInfo, $useLocationCache);

        if (!isset($location[$locationKey])) {
            return false;
        }

        return $location[$locationKey];
    }

    protected function getVisitorGeolocator()
    {
        if ($this->visitorGeolocator === null) {
            $this->visitorGeolocator = new VisitorGeolocator();
        }

        return $this->visitorGeolocator;
    }

    protected function getUserInfo(Request $request, Visitor $visitor)
    {
        $ipAddress = $this->getIpAddress($visitor->getVisitorColumn('location_ip'), $request);
        $language  = $request->getBrowserLanguage();

        $userInfo  = array('lang' => $language, 'ip' => $ipAddress);

        return $userInfo;
    }

    private function getIpAddress($anonymizedIp, \Piwik\Tracker\Request $request)
    {
        $privacyConfig = new PrivacyManagerConfig();

        $ip = $request->getIp();

        if ($privacyConfig->useAnonymizedIpForVisitEnrichment) {
            $ip = $anonymizedIp;
        }

        $ipAddress = IPUtils::binaryToStringIP($ip);

        return $ipAddress;
    }
}
