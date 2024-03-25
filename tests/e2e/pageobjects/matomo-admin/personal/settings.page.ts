/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import MatomoAdminPage from '../../matomo-admin.page.js';

class PersonalSettingsPage extends MatomoAdminPage {
  async open() {
    const result = await super.open('UsersManager.userSettings');
    await $('#userSettingsTable').waitForDisplayed();
    await $('.pluginSettings input').waitForDisplayed();
    await browser.pause(1000);
    return result;
  }
}

export default new PersonalSettingsPage();
