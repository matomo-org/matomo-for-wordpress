/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import WpAdminPage from './page.js';
import {$, browser} from "@wdio/globals";

export const MATOMO_UPDATE_ROW_SELECTOR = 'tr.plugin-update-tr[data-slug="matomo"]';

class PluginsAdminPage extends WpAdminPage {
  async open() {
    const result = await super.open('/wp-admin/plugins.php');
    await $('tr[data-slug="matomo"]').waitForExist({ timeout: 30000 });
    return result;
  }

  async hasUpdateAvailable() {
    return await this.hasElement(MATOMO_UPDATE_ROW_SELECTOR);
  }

  async updateRowText() {
    return await browser.execute((s) => window.jQuery(s).text(), MATOMO_UPDATE_ROW_SELECTOR);
  }

  async clickUpdateNow() {
    await browser.execute((s) => {
      window.jQuery(`${s} .update-link`)[0].click();
    }, MATOMO_UPDATE_ROW_SELECTOR);

    await browser.waitUntil(async () => {
      return /update\.php/.test(await browser.execute(() => window.location.href));
    }, { timeout: 60000 });
  }

  async installedVersion() {
    return await browser.execute(() => {
      const text = window.jQuery('tr[data-slug="matomo"] .plugin-version-author-uri').text();
      const match = text.match(/Version (\d+\.\d+\.\d+)/);
      return match ? match[1] : null;
    });
  }

  async isMatomoActive() {
    return await browser.execute(() => window.jQuery('tr[data-slug="matomo"]').hasClass('active'));
  }

  async hideNonMatomoRows() {
    await browser.execute(() => {
      window.jQuery('tr[data-slug]:not([data-slug="matomo"])').hide();
    });
  }

  async hideNotifications() {
    await browser.execute(() => {
      window.jQuery('div[id="message"]').hide();
    });
  }

  async hidePluginFilters() {
    await browser.execute(() => {
      window.jQuery('.subsubsub').hide();
    });
  }

  async hidePluginVersion() {
    await browser.execute(() => {
      window.jQuery('.plugin-version-author-uri').each(function () {
        window.jQuery(this).html(
          window.jQuery(this).html().replace(/Version \d+\.\d+\.\d+/g, 'Version REMOVED')
        );
      });
    });
  }
}

export default new PluginsAdminPage();
