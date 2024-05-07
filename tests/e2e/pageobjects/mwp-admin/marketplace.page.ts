/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { $, browser } from '@wdio/globals';
import * as fs from 'fs';
import * as path from 'path';
import MwpPage from './page.js';
import * as url from 'url';

const dirname = path.dirname(url.fileURLToPath(import.meta.url));

const DOWNLOADS_DIR = path.join(dirname, '..', '..', 'downloads');

class MwpMarketplaceSetupWizard {
  async downloadPlugin(): Promise<string> {
    const downloadUrl = await browser.execute(() => window.jQuery('.download-plugin').attr('href'));
    const downloadPath = path.join(DOWNLOADS_DIR, path.basename(downloadUrl));

    await $('.download-plugin').click();
    await browser.waitUntil(() => fs.existsSync(downloadPath), 5000);

    return downloadPath;
  }

  async goToPluginsAdmin(): Promise<void> {
    await $('.open-plugin-upload').click();

    await browser.waitUntil(async () => {
      await browser.pause(2000);
      try {
        await browser.switchWindow(/\/wp-admin\/plugin-install\.php\?tab=upload/);
        return true;
      } catch (e) {
        return false;
      }
    }, { timeout: 15000 });

    await $('#pluginzip').waitForDisplayed();
  }

  async uploadPluginAndActivate(pathToPlugin: string): Promise<void> {
    await $('#pluginzip').setValue(pathToPlugin);
    await browser.pause(500);
    await $('#install-plugin-submit').click();
    await $('.button=Activate Plugin').waitForDisplayed();

    await $('.button=Activate Plugin').click();
    await browser.waitUntil(() => {
      return browser.execute(() => /\/wp-admin\/plugins\.php$/.test(window.location.pathname));
    });

    await browser.closeWindow();
    await browser.switchWindow(/page=matomo-marketplace/);
  }

  async waitForReload(): Promise<void> {
    await $('#tgmpa-plugins .install').waitForDisplayed({ timeout: 30000 });
  }
}

class MwpMarketplacePage extends MwpPage {
  readonly setupWizard = new MwpMarketplaceSetupWizard();

  async open() {
    return await super.open('/wp-admin/admin.php?page=matomo-marketplace');
  }

  async openInstallPluginsTab() {
    await $('a.nav-tab=Install Plugins').click();

    await $('td.column-version,.matomo-marketplace-wizard').waitForExist({ timeout: 30000 });

    if (await $('td.column-version').isExisting()) {
      // remove most plugins so the screenshot will stay the same over time
      await this.removeThirdPartyPlugins();

      // remove version strings so test will pass when plugin requirements
      // change
      await this.removeVersionStrings();

      await this.removePluginCounts();
    }
  }

  async removePluginCounts() {
    // remove number of plugins so test will pass when new plugins are released/
    // other plugins are removed
    await browser.execute(() => {
      window.jQuery('.subsubsub .count').each((i, e) => {
        window.jQuery(e).text('()');
      });
    });
  }

  async removeThirdPartyPlugins() {
    await browser.execute(() => {
      window.jQuery('tbody#the-list > tr').each((i, e) => {
        if (window.jQuery('td[data-colname="Developer"]', e).text() !== 'matomo-org'
          || window.jQuery('td[data-colname="Plugin"]>strong>a', e).text() === 'Force SSL' // test environment does not use ssl
        ) {
          window.jQuery(e).remove();
        }
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

    await $('#wpbody-content form#tgmpa-plugins').waitForDisplayed({ timeout: 30000 });
  }

  async installPlugin(plugin: string) {
    await browser.execute((p) => {
      window.jQuery(`.check-column input[value="${p}"]`).closest('tr').find('span.install > a')[0].click();
    }, plugin);

    await $('#wpbody-content p a.button-primary').waitForDisplayed({ timeout: 30000 });
  }

  async activateInstalledPlugin() {
    await $('#wpbody-content p a.button-primary').click();
    await $('table.plugins').waitForDisplayed({ timeout: 30000 });
  }

  async showToActivatePlugins() {
    await $('.subsubsub li.activate > a').click();
    await $('.subsubsub li.activate > a.current').waitForDisplayed({ timeout: 30000 });
  }

  async removeVersionStrings() {
    await browser.execute(() => {
      window.jQuery('td.column-version').each((i, e) => {
        window.jQuery(e).html(
          window.jQuery(e).html().replace(/\d+\.\d+\.\d+(-[a-zA-Z0-9]+)?/g, '-')
        );
      });
    });
  }
}

export default new MwpMarketplacePage();
