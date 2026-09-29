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
 * Constrains a tracker endpoint to a resource that cannot be a file somebody uploaded.
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
    const ALLOWED_EXTENSIONS = ['js', 'php'];

    public function validate($value)
    {
        if ($this->isValueBare($value)) {
            // whether an empty value is acceptable is NotEmpty()'s decision, not ours
            return;
        }

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

        if ('' === $path || substr($path, -1) === '/') {
            return; // a directory
        }

        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));

        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new Exception(Piwik::translate('WordPress_TagManagerTrackerEndpointNotAllowed'));
        }
    }
}
