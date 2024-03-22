/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { $, browser } from '@wdio/globals';
import MwpPage from './page.js';

class MwpMarketplacePage extends MwpPage {
  async open() {
    return await super.open('/wp-admin/admin.php?page=matomo-marketplace');
  }

  async openInstallPluginsTab() {
    await $('a.nav-tab=Install Plugins').click();

    await $('td.column-version').waitForExist();

    // remove most plugins so the screenshot will stay the same over time
    await browser.execute(() => {
      window.jQuery('tbody#the-list > tr').each((i, e) => {
        if (window.jQuery('td[data-colname="Developer"]', e).text() !== 'matomo-org') {
          window.jQuery(e).remove();
        }
      });
    });

    // remove version strings so test will pass when plugin requirements
    // change
    await browser.execute(() => {
      window.jQuery('td.column-version').each((i, e) => {
        window.jQuery(e).html(
          window.jQuery(e).html().replace(/\d+\.\d+\.\d+(-[a-zA-Z0-9]+)?/g, '-')
        );
      });
    });
  }

  async openSubscriptionsTab() {
    await $('a.nav-tab=Subscriptions').click();
  }

  async setSubscriptionLicense(license: string) {
    if (!license) {
      throw new Error('no license specified in TEST_SHOP_LICENSE environment var, cannot run test');
    }

    // just for screenshots, make sure the license does not display
    await browser.execute(() => {
      window.jQuery('input[name="matomo_license_key"]').attr('type', 'password');
    });

    await browser.execute((l) => {
      window.jQuery('input[name="matomo_license_key"]').val(l);
    }, license);

    await $('#wpbody-content .button-primary').click();

    await $('#wpbody-content form#tgmpa-plugins').waitForDisplayed();
  }

  async installPlugin(plugin: string) {
    await browser.execute((p) => {
      window.jQuery(`input#${p}`).closest('tr').find('span.install > a')[0].click();
    }, plugin);

    await $('#wp-content p a.button-primary').waitForDisplayed();
  }

  async activateInstalledPlugin() {
    await $('#wp-content p a.button-primary').click();
    await $('table.plugins').waitForDisplayed();
  }

  async showToActivatePlugins() {
    await $('.subsubsub li.activate > a').click();
    await $('.subsubsub li.activate > a.current').waitForDisplayed();
  }
}

export default new MwpMarketplacePage();
