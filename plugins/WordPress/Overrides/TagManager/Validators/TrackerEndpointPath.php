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

/**
 * Restricts custom endpoints to Matomo's tracker resources below the validated Matomo app URL.
 */
class TrackerEndpointPath extends BaseValidator
{
    /**
     * Everything else is refused rather than interpreted. The path is compared after one round of
     * decoding (see validate()), so a "%" or a NUL byte that survives that is an attempt to have
     * this and the browser read the same value differently.
     */
    const ALLOWED_CHARACTERS = '#^[A-Za-z0-9._~/-]*$#';

    /**
     * @var string[]
     */
    const ALLOWED_PATHS = [
        'custom.js',
        'matomo.js',
        'piwik.js',
        'matomo.min.js',
        'js/tracker.js',
        'custom.php',
        'matomo.php',
        'piwik.php',
        'js/tracker.php',
        'js/',
    ];

    public function validate($value)
    {
        if (!is_string($value)) {
            throw new Exception(Piwik::translate('WordPress_TagManagerTrackerEndpointNotAllowed'));
        }

        // remove a '?...' or '#...' from the path so no one can use that to bypass the suffix
        // and point to another file
        $path = preg_split('/[?#]/', $value, 2)[0];

        // "%2e%2e" addresses the same directory as "..", and "payload.txt%00.js" ends in ".js"
        // only until it is decoded, so the comparison has to be made on the decoded path
        $path = rawurldecode($path);

        if (!preg_match(self::ALLOWED_CHARACTERS, $path)) {
            throw new Exception(Piwik::translate('WordPress_TagManagerTrackerEndpointCharacterNotAllowed'));
        }

        // Leading slashes are removed by the secured-template transform before the value is
        // used. Apply the same normalization here so validation covers the final relative path.
        $path = ltrim($path, '/');

        if ('' === $path || !in_array($path, self::ALLOWED_PATHS, true)) {
            throw new Exception(Piwik::translate('WordPress_TagManagerTrackerEndpointNotAllowed'));
        }
    }
}
