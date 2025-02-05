/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

import { Matomo } from 'CoreHome';

export { default as UseOptOutShortCode } from './UseOptOutShortCode/UseOptOutShortCode.vue';
export { default as PluginMeasurableSettings } from './PluginMeasurableSettings/PluginMeasurableSettings.vue';

Matomo.on('Matomo.topControlsRendered', () => {
  $('.top_controls .top_bar_sites_selector').hide();
});
