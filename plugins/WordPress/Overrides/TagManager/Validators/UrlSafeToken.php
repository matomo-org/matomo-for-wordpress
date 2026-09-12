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
 * For template values that are concatenated into a UR. Constrains them to characters that cannot
 * change what that URL addresses.
 */
class UrlSafeToken extends BaseValidator
{
    const ALLOWED_CHARACTERS = '#^[A-Za-z0-9._~-]*$#';

    public function validate($value)
    {
        if ($this->isValueBare($value)) {
            // whether an empty value is acceptable is NotEmpty()'s decision, not ours
            return;
        }

        if (!is_string($value) || !preg_match(self::ALLOWED_CHARACTERS, $value)) {
            throw new Exception(Piwik::translate('WordPress_TagManagerUrlTokenCharacterNotAllowed'));
        }
    }
}
