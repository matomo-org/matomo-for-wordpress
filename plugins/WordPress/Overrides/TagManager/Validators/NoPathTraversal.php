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
 * Rejects a value that walks back up out of the URL it is appended to, eg, with a `../`.
 */
class NoPathTraversal extends BaseValidator
{
    public function validate($value)
    {
        if ($this->isValueBare($value)) {
            return;
        }

        if (!is_string($value)) {
            throw new Exception(Piwik::translate('WordPress_TagManagerPathTraversalNotAllowed'));
        }

        // "%2e%2e" is a dot-dot segment to a URL parser just as ".." is, so it is the decoded
        // value that has to be checked
        if (strpos(rawurldecode($value), '..') !== false) {
            throw new Exception(Piwik::translate('WordPress_TagManagerPathTraversalNotAllowed'));
        }
    }
}
