<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo\Workarounds;

/**
 * Workarounds for bugs in the Fluent SMTP wordpress plugin.
 */
class FluentSmtp
{
    /**
     * Worksaround this bug in Fluent SMTP: https://github.com/WPManageNinja/fluent-smtp/issues/180
     * by creating a proxy to the global PHPMailer object that disables the addAttachment() method.
     *
     * Should be used in a phpmailer_init action after manually adding attachments to the original
     * PHPMailer instance.
     */
    public static function make_php_mailer_proxy( $phpmailer ) {
        if ( ! is_plugin_active( 'fluent-smtp/fluent-smtp.php' ) ) {
            return $phpmailer;
        }

        return new class( $phpmailer ) {
            private $wrapped;

            public function __construct( $phpmailer ) {
                $this->wrapped = $phpmailer;
            }

            public function __get( $name )
            {
                return $this->wrapped->$name;
            }

            public function __set( $name, $value )
            {
                $this->wrapped->$name = $value;
            }

            public function __call( $name , $arguments ) {
                if ( $name == 'addAttachment' ) {
                    return;
                }

                return call_user_func_array([$this->wrapped, $name], $arguments);
            }
        };
    }

    // fluent smtp will check if the global phpmailer object's class is actually PHPMailer, and if not, abort
    // so we need to make sure it doesn't see our wrapped proxy again.
    public static function unset_phpmailer() {
        global $phpmailer;

        if ( ! is_plugin_active( 'fluent-smtp/fluent-smtp.php' ) ) {
            return;
        }

        $phpmailer = null;
    }
}
