<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\UserCountry\Columns;

use Piwik\Plugins\UserCountry\LocationProvider;
use Piwik\Tracker\Request;
use Piwik\Tracker\Visitor;
use Piwik\Tracker\Action;

class Region extends Base
{
    protected $columnName = 'location_region';
    protected $columnType = 'char(3) DEFAULT NULL';
    protected $type = self::TYPE_TEXT;
    protected $category = 'UserCountry_VisitLocation';
    protected $segmentName = 'regionCode';
    protected $nameSingular = 'UserCountry_Region';
    protected $namePlural = 'UserCountryMap_Regions';
    protected $acceptValues = '01 02, OR, P8, etc.<br/>eg. region=BFC;country=fr';

    /**
     * @param Request $request
     * @param Visitor $visitor
     * @param Action|null $action
     * @return mixed
     */
    public function onNewVisit(Request $request, Visitor $visitor, $action)
    {
        $value = $this->getUrlOverrideValueIfAllowed('region', $request);
        if ($value !== false) {
            $value = substr($value, 0, 3);
            return $value;
        }

        $userInfo = $this->getUserInfo($request, $visitor);

        return $this->getLocationDetail($userInfo, LocationProvider::REGION_CODE_KEY);
    }

    /**
     * @param Request $request
     * @param Visitor $visitor
     * @param Action|null $action
     * @return int
     */
    public function onExistingVisit(Request $request, Visitor $visitor, $action)
    {
        return $this->getUrlOverrideValueIfAllowed('region', $request);
    }

    /**
     * @param Request $request
     * @param Visitor $visitor
     * @param Action|null $action
     * @return mixed
     */
    public function onAnyGoalConversion(Request $request, Visitor $visitor, $action)
    {
        return $visitor->getVisitorColumn($this->columnName);
    }
}
