/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { $ } from '@wdio/globals';
import MatomoReportingPage from '../../matomo-reporting.page.js';

class RealTimeMapPage extends MatomoReportingPage {
  async open() {
    const result = await super.open('General_Visitors.UserCountryMap_RealTimeMap');

    await $('circle').waitForDisplayed();
    await browser.execute(() => {
      $('.realTimeMap_datetime').hide();
    });

    return result;
  }
}

export default new RealTimeMapPage();
