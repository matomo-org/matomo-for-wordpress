/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { browser, $ } from '@wdio/globals';
import MwpPage from './page.js';

class MwpSettingsPage extends MwpPage {
  open() {
    return super.open('/wp-admin/admin.php?page=matomo-settings');
  }

  async enableTagManagerTracking() {
    await browser.execute(() => {
      window.jQuery('#track_mode').val('tagmanager').change();
    });

    await browser.execute(() => {
      window.jQuery('tr.matomo-track-option-tagmanager input[type="checkbox"]').first().prop('checked', true);
    });

    await browser.execute(() => {
      window.jQuery('.matomo-tracking-form .submit > input')[0].click();
    });

    await browser.pause(1000);
  }

  async disableTagManagerTracking() {
    await browser.execute(() => {
      window.jQuery('#track_mode').val('default').change();
    });

    await browser.execute(() => {
      window.jQuery('.matomo-tracking-form .submit > input').click();
    });

    await browser.pause(1000);
  }

  async openAccessTab() {
    await $('a.nav-tab=Access').click();
  }

  async openPrivacyTab() {
    await $('a.nav-tab*=Privacy').click();
  }

  async openExclusionsTab() {
    await $('a.nav-tab=Exclusions').click();
  }

  async openGeolocationTab() {
    await $('a.nav-tab=Geolocation').click();
  }

  async openAdvancedTab() {
    await $('a.nav-tab=Advanced').click();
  }

  async openMeasurableSettings(pluginDisplayName: string) {
    await $(`a.nav-tab=${pluginDisplayName}`).click();
    await $('iframe').waitForDisplayed();
    await browser.pause(2000); // wait for iframe resizer to activate
  }

  async setSeoWebVitalsSettingValue(value: string) {
    await browser.execute((v) => {
      window.jQuery('#plugin_measurable_settings').contents()
        .find('textarea[name="check_urls"]').val(v)[0].dispatchEvent(new Event('change'));
    }, value);
    await browser.pause(250); // for the value in Vue to update
    await browser.execute(() => {
      window.jQuery('#plugin_measurable_settings').contents()
        .find('.settingsFormFooter input')[0].click();
    });
    await browser.pause(3000);
  }
}

export default new MwpSettingsPage();
