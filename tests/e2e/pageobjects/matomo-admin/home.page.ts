/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { $ } from '@wdio/globals';
import MatomoAdminPage from './matomo-admin.page.js';

class AdminHomePage extends MatomoAdminPage {
  async open() {
    const result = await super.open('CoreAdminHome.home');

    await $('.widget').waitForDisplayed();
    await browser.pause(500);

    return result;
  }
}

export default new AdminHomePage();
