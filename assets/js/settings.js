/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

window.jQuery(document).ready(function ($) {
  // generated tracking code update
  if (typeof mtmTrackingSettingsAjax !== 'undefined' && mtmTrackingSettingsAjax.ajax_url) {
    function updateGeneratedTrackingCode() {
      var settings = $('form#tracking-settings').serializeArray().reduce(function (accumulator, current) {
        if (/^_/.test(current.name)) {
          return accumulator;
        }
        accumulator[current.name] = current.value;
        return accumulator;
      }, {});

      $.post(
        mtmTrackingSettingsAjax.ajax_url,
        Object.assign(settings, {
          _ajax_nonce: mtmTrackingSettingsAjax.nonce,
          action: 'matomo_generate_tracking_code',
        }),
        function (data) {
          if (data) {
            $('#generated_tracking_code').text(data.script);
            $('#generated_noscript_code').text(data.noscript);
          }
        },
      );
    }

    $('.auto-tracking-form').on('change', updateGeneratedTrackingCode);
    updateGeneratedTrackingCode();
  }

  function beforePageUnload(e) {
    var currentFormContents = $('#tracking-settings').serialize();
    if (initialFormContents !== currentFormContents) {
      e.preventDefault();
    }
  }

  // warn if user has unsaved changes
  var initialFormContents = $('#tracking-settings').serialize();
  window.addEventListener('beforeunload', beforePageUnload);
  $('form#tracking-settings').on('submit', function () {
    window.removeEventListener('beforeunload', beforePageUnload);
  });

  // even spacing between post types
  var postLengths = $('.post-types > div').toArray().map((function (e) { return $(e).width(); }));
  var maxPostLength = Math.max.apply(null, postLengths);
  $('.post-types > div').each(function () {
    $(this).width(maxPostLength);
  });
});
