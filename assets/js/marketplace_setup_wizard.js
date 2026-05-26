/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

window.jQuery(document).ready(function ($) {
  function pollForPluginActivation(setActiveClass) {
    if (setActiveClass) {
      $('.wizard-waiting-for').addClass('active').find('.waiting-for-install').show();
    } else {
      $('.wizard-waiting-for').show();
    }

    var interval = setInterval(function () {
      $.post(mtmMarketplaceWizardAjax.ajax_url, {
        _ajax_nonce: mtmMarketplaceWizardAjax.is_active_nonce,
        action: 'matomo_is_marketplace_active',
      }, function (data) {
        if (data.active) {
          if (!setActiveClass) {
            $('.wizard-waiting-for').hide();
          } else {
            $('.wizard-waiting-for .waiting-for-activation').hide();
          }

          $('.wizard-reloading').show();

          // reload after the dom has had a chance to update
          setTimeout(function () {
            window.location.reload();
          });

          clearInterval(interval);
        } else if (data.installed && setActiveClass) {
          $('.wizard-waiting-for .waiting-for-install').hide();
          $('.wizard-waiting-for .waiting-for-activation').show();
        }
      });
    }, 2000);
  }

  function activateMarketplace(e) {
    e.preventDefault();
    e.stopPropagation();

    $('.wizard-waiting-for').show();

    $.post(mtmMarketplaceWizardAjax.ajax_url, {
      _ajax_nonce: mtmMarketplaceWizardAjax.activate_nonce,
      action: 'matomo_activate_marketplace',
    }, pollForPluginActivation.bind(null, false));
  }

  if (typeof mtmMarketplaceWizardAjax !== 'undefined' && mtmMarketplaceWizardAjax.ajax_url) {
    var pollFn = pollForPluginActivation;
    var activateFn = activateMarketplace;
    if (mtmMarketplaceWizardAjax.is_welcome_page) {
      pollFn = pollForPluginActivation.bind(null, true);
      activateFn = activateMarketplace.bind(null, true);
    }

    $('.matomo-marketplace-wizard-body .open-plugin-upload').on('click', pollFn);
    $('.matomo-marketplace-wizard-body .activate-plugin').on('click', activateFn);
  }
});
