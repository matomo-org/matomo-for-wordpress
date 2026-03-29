/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { browser, $ } from '@wdio/globals';
import MatomoAdminPage from '../../matomo-admin.page.js';

class GdprToolsPage extends MatomoAdminPage {
  async open() {
    const result = super.open('PrivacyManager.gdprTools');

    await $('.segment-generator').waitForDisplayed();
    await browser.waitUntil(async () => {
      return browser.execute(() => !$('.loadingPiwik').is(':visible'));
    }, { timeout: 20000 });

    // if the wait above doesn't work, just hide the gif
    this.addStylesToPage(`
      .segment-loading > .matomo-loader {
        display: none !important;
      }
    `);
    await browser.pause(500);

    return result;
  }
}

export default new GdprToolsPage();
