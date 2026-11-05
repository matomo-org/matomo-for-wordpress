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

/** Refuses appended tracking parameters that can carry authentication material. */
class NoAuthenticationParameters extends BaseValidator
{
    public function validate($value)
    {
        if ($this->isValueBare($value) || '' === trim((string) $value)) {
            return;
        }

        if (!is_scalar($value)) {
            throw new Exception(Piwik::translate('WordPress_TagManagerAuthenticationParameterNotAllowed'));
        }

        foreach (preg_split('/[&;]/', (string) $value) as $part) {
            $name = rawurldecode(explode('=', $part, 2)[0]);
            $normalised = strtolower(preg_replace('/[^a-z0-9]/i', '', $name));

            if (preg_match('/(?:nonce|auth|token|password|secret|credential)/', $normalised)) {
                throw new Exception(Piwik::translate('WordPress_TagManagerAuthenticationParameterNotAllowed'));
            }
        }
    }
}
