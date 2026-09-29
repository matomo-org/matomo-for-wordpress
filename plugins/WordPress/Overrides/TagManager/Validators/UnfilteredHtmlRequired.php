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
 * Refuses all parameter values. Should be used to prevent the use of a template when the
 * user does not have the `unfilterd_html` capability.
 */
class UnfilteredHtmlRequired extends BaseValidator
{
    public function validate($value)
    {
        throw new Exception(Piwik::translate('WordPress_TagManagerUnfilteredHtmlRequired'));
    }
}
