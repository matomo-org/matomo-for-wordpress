/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

import { Matomo, NotificationsStore, translate } from 'CoreHome';

export { default as UseOptOutShortCode } from './UseOptOutShortCode/UseOptOutShortCode.vue';
export { default as PluginMeasurableSettings } from './PluginMeasurableSettings/PluginMeasurableSettings.vue';

// hide site selector
Matomo.on('Matomo.topControlsRendered', () => {
  $('.top_controls .top_bar_sites_selector').hide();
});

// if AI bot tracking is not enabled, show notification on AI bot tracking page linking
// to MWP setting
Matomo.on('ReportingPage.loadPage', (params: { category: string, subcategory: string }) => {
  if (
    params.category !== 'General_AIAssistants'
    || params.subcategory !== 'BotTracking_AIBotsOverview'
  ) {
    return;
  }

  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  if ((Matomo as any).isAiBotTrackingEnabledInMwp) {
    return;
  }

  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const settingsUrl = Matomo.helper.htmlEntities(`${(Matomo as any).mwpHomeUrl}/wp-admin/admin.php?page=matomo-settings#track_ai_bots-field`);

  NotificationsStore.show({
    context: 'info',
    type: 'transient',
    message: translate('WordPress_AIBotTrackingIsNotEnabled', `<a href="${settingsUrl}" target="_blank">`, '</a>'),
    noclear: true,
  });
});
