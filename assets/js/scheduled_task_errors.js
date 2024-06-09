/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

window.jQuery(document).ready(function ($) {
  if (typeof mtmScheduledTaskErrorAjax !== 'undefined' && mtmScheduledTaskErrorAjax.ajax_url) {
    $('body').on('click', '.matomo-cron-error .notice-dismiss', function (e) {
      $.post(mtmScheduledTaskErrorAjax.ajax_url, {
        _ajax_nonce: mtmScheduledTaskErrorAjax.nonce,
        action: 'mtm_remove_cron_error',
        matomo_job_id: $(e.target).closest('.matomo-cron-error').data('job')
      });
    });
  }
});
