<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\SitesManager\SiteContentDetection;

use Piwik\Url;

class Sharepoint extends SiteContentDetectionAbstract
{
    public static function getName(): string
    {
        return 'SharePoint';
    }

    public static function getContentType(): int
    {
        return self::TYPE_CMS;
    }

    public static function getInstructionUrl(): ?string
    {
        return Url::addCampaignParametersToMatomoLink('https://matomo.org/faq/how-to-install/faq_19424/');
    }

    public function isDetected(?string $data = null, ?array $headers = null): bool
    {
        $needle = 'content="Microsoft SharePoint';
        return (strpos($data, $needle) !== false);
    }
}
