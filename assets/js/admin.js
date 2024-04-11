/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

jQuery(document).on( 'click', '#matomo-referral .notice-dismiss', function () {
    var data = {'action': 'matomo_referral_dismiss_admin_notice'};
    jQuery.post( ajaxurl, data );
});

jQuery(document).on( 'click', '#matomo-referral .matomo-dismiss-forever', function () {
    var data = {'action': 'matomo_referral_dismiss_admin_notice', forever: '1'};
    jQuery.post( ajaxurl, data );
});

jQuery(document).on( 'click', '#matomo-systemreporterrors .notice-dismiss', function () {
    var data = {'action': 'matomo_system_report_error_dismissed'};
    jQuery.post( ajaxurl, data );
});
