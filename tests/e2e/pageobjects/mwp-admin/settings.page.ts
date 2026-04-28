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
    await this.selectTrackMode('tagmanager');

    await browser.execute(() => {
      window.jQuery('.tagmanager-container-select input[type="checkbox"]').first().prop('checked', true);
    });

    await this.saveSettings();

    await browser.pause(1000);
  }

  async disableTagManagerTracking() {
    await this.selectTrackMode('default');

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

  async expandAllTrackingSettingsSections() {
    await browser.execute(() => {
      window.jQuery('.collapsible-settings:not(.expanded) > h2').each(function () {
        window.jQuery(this).click();
      });

      window.jQuery('#showGeneratedTrackingCode > a')[0].click();
    });
    await browser.pause(200);
  }

  async selectTrackMode(trackMode: string) {
    await browser.$(`input[name="matomo[track_mode]"][value="${trackMode}"]`).click();
  }

  async changeSomeAutoTrackingSettings() {
    await browser.execute(() => {
      window.jQuery('input[name="matomo[disable_cookies]"]').prev('.matomo-toggle').find('input').click();
      window.jQuery('input[name="matomo[track_crossdomain_linking]"]').prev('.matomo-toggle').find('input').click();
      window.jQuery('input[name="matomo[set_download_classes]"]').val('a|b|c');
    });
    await browser.pause(500);
  }

  async saveSettings() {
    await browser.$('p.submit > input').click();
    await browser.waitUntil(() => {
      return browser.execute(() => {
        return window.jQuery('.updated.notice p:contains(Settings have been updated successfully)').length > 0;
      });
    });
    await browser.pause(500);
  }

  async removeTagManagerContainerIds() {
    await browser.execute(() => {
      window.jQuery('.tagmanager-container-select').html(
        window.jQuery('.tagmanager-container-select').html().replace(/\(ID: [a-zA-Z0-9]+\)/g, '(ID: REMOVED)')
      );

      window.jQuery('textarea').each(function () {
        window.jQuery(this).html(
          window.jQuery(this).html().replace(/container_[a-zA-Z0-9]+\.js/g, 'container_REMOVED.js')
        );
      });
    });
  }
}

export default new MwpSettingsPage();
