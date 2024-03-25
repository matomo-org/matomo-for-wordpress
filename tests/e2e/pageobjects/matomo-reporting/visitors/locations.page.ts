/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { $, browser } from '@wdio/globals';
import MatomoReportingPage from '../../matomo-reporting.page.js';

class LocationsPage extends MatomoReportingPage {
  async open() {
    const result = await super.open('General_Visitors.UserCountry_SubmenuLocations');
    await browser.waitUntil(async () => {
      return await $('.UserCountryMap_map.kartograph').isDisplayed();
    });
    await browser.execute(() => {
      $('.UserCountryMap_map.kartograph').css('height', '239px'); // the height can change randomly in CI it seems
    });
    await browser.pause(500);
    return result;
  }
}

export default new LocationsPage();
