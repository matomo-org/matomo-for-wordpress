/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import MatomoAdminPage from '../../matomo-admin.page.js';

class UsersOptOutPage extends MatomoAdminPage {
  async open() {
    const result = await super.open('PrivacyManager.usersOptOut');
    await $('.matomo-save-button').waitForDisplayed();
    return result;
  }
}

export default new UsersOptOutPage();
