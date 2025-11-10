/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

import { Matomo, AjaxHelper } from 'CoreHome';

export { default as UseOptOutShortCode } from './UseOptOutShortCode/UseOptOutShortCode.vue';
export { default as PluginMeasurableSettings } from './PluginMeasurableSettings/PluginMeasurableSettings.vue';

Matomo.on('AjaxHelper.send', (ajax: AjaxHelper) => {
  // eslint-disable-next-line
  ajax.getUrl = `${(window.piwik as any).mwpHomeUrl}/wp-admin/admin.php?page=matomo-reporting`;
  console.log('here?', ajax.getUrl);
});

Matomo.on('Matomo.topControlsRendered', () => {
  $('.top_controls .top_bar_sites_selector').hide();
});
