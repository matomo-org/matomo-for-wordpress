<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\WordPress\Overrides\TagManager\Validators;

use Piwik\Piwik;
use Piwik\Validators\BaseValidator;
use Piwik\Validators\Exception;

/** Limits custom image requests to same-site image files without query-triggered actions. */
class CustomImageUrl extends BaseValidator
{
    public function validate($value)
    {
        if ($this->isValueBare($value)) {
            return;
        }

        (new SiteOwnUrl())->validate($value);

        if (!is_string($value)) {
            throw new Exception(Piwik::translate('WordPress_TagManagerUrlNotAllowed'));
        }

        $parts = wp_parse_url($value);
        $path = isset($parts['path']) ? rawurldecode($parts['path']) : '';
        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        $allowedExtensions = ['avif', 'bmp', 'gif', 'ico', 'jpeg', 'jpg', 'png', 'svg', 'webp'];

        // Query selectors can turn a same-site image request into an action such as wc-ajax.
        $query = isset($parts['query']) ? strtolower(rawurldecode($parts['query'])) : '';
        if (!in_array($extension, $allowedExtensions, true)
            || preg_match('/(?:^|[&;])(?:action|wc-ajax|rest_route|_wpnonce|nonce)=/', $query)
        ) {
            throw new Exception(Piwik::translate('WordPress_TagManagerUrlNotAllowed'));
        }
    }
}
