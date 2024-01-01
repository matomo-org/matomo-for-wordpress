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

    await browser.pause(500);

    return result;
  }
}

export default new GdprToolsPage();
