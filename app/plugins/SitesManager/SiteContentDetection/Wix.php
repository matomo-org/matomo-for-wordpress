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

class Wix extends SiteContentDetectionAbstract
{
    public static function getName(): string
    {
        return 'Wix';
    }

    public static function getContentType(): int
    {
        return self::TYPE_CMS;
    }

    public static function getInstructionUrl(): ?string
    {
        return Url::addCampaignParametersToMatomoLink('https://matomo.org/faq/new-to-piwik/how-do-i-install-the-matomo-analytics-tracking-code-on-wix/');
    }

    public function isDetected(?string $data = null, ?array $headers = null): bool
    {
        $needle = 'X-Wix-Published-Version';
        return (strpos($data, $needle) !== false);
    }
}
