/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import MatomoAdminPage from '../../matomo-admin.page.js';

class GdprToolsPage extends MatomoAdminPage {
  async open() {
    const result = super.open('PrivacyManager.gdprTools');

    await $('.segment-generator').waitForDisplayed();
    await browser.waitUntil(async () => {
      return !(await $('.segment-generator > .loadingPiwik').isDisplayed());
    }, { timeout: 20000 });

    return result;
  }
}

export default new GdprToolsPage();
