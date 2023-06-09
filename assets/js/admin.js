jQuery(document).on( 'click', '#matomo-referral .notice-dismiss', function () {
    var data = {'action': 'matomo_referral_dismiss_admin_notice'};
    jQuery.post( ajaxurl, data );
});jQuery(document).on( 'click', '#matomo-referral .matomo-dismiss-forever', function () {
    var data = {'action': 'matomo_referral_dismiss_admin_notice', forever: '1'};
    jQuery.post( ajaxurl, data );
});
jQuery(document).on( 'click', '#matomo-systemreporterrors .notice-dismiss', function () {
    var data = {'action': 'matomo_system_report_error_dismissed'};
    jQuery.post( ajaxurl, data );
});
