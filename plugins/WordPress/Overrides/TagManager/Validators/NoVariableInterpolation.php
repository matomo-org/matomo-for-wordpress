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
 * Rejects a parameter value that references another Tag Manager variable.
 *
 * Context\BaseContext::parameterToVariableJs() turns any scalar parameter containing "{{Name}}"
 * into a client side variable lookup that is concatenated at runtime, so a value like
 * "{{MyConstant}}" is resolved in the visitor's browser and there is nothing to inspect when it
 * is saved. Any context where the saved value has to be constrained (eg, the author not having
 * `unfiltered_html`), an interpolation has to be refused outright.
 */
class NoVariableInterpolation extends BaseValidator
{
    public function validate($value)
    {
        if (!is_scalar($value)) {
            return;
        }

        // "}}" is refused as well as "{{", so a value cannot be left holding one half of a
        // reference for something else to complete later
        if (preg_match('/\{\{|\}\}/', (string) $value)) {
            throw new Exception(Piwik::translate('WordPress_TagManagerNoVariableInterpolation'));
        }
    }
}
