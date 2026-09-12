<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\WordPress\Overrides\TagManager;

/**
 * Whether the extra validations SecuredTemplate adds are currentl enabled or not.
 */
class SecuredTemplateConstraints
{
    /**
     * @var bool
     */
    private static $suspended = false;

    /**
     * @return bool
     */
    public static function areSuspended()
    {
        return self::$suspended;
    }

    /**
     * Runs the callback with extra validations turned off. Only ever used for re-saving values
     * this Matomo already has stored, never for anything the request carried.
     *
     * @param callable $callback
     * @return mixed whatever the callback returns
     */
    public static function suspendedFor(callable $callback)
    {
        $previous = self::$suspended;
        self::$suspended = true;

        try {
            return $callback();
        } finally {
            self::$suspended = $previous;
        }
    }
}
