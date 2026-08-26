/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import WpAdminPage from './page.js';
import { REQUIREMENTS_FAQ_PATH } from './update-plugin.page.js';
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
  }

  async waitForUpdateRowText(text: string) {
    await browser.waitUntil(
      async () => (await this.updateRowText()).includes(text),
      { timeout: 60000, timeoutMsg: `the plugin update row never contained "${text}"` }
    );
  }

  async updateNowUrl(): Promise<string> {
    const url = await browser.execute(
      (s) => window.jQuery(`${s} .update-link`).attr('href'),
      MATOMO_UPDATE_ROW_SELECTOR
    ) as string;

    if (!url) {
      throw new Error(`no update link found in ${MATOMO_UPDATE_ROW_SELECTOR}`);
    }

    return url;
  }

  async hasRequirementsFaqLinkInUpdateRow() {
    return await this.hasElement(`${MATOMO_UPDATE_ROW_SELECTOR} a[href*="${REQUIREMENTS_FAQ_PATH}"]`);
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
