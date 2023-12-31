/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { $, $$ } from '@wdio/globals';
import MatomoReportingPage from '../matomo-reporting.page.js';

class DashboardPage extends MatomoReportingPage {
  async open() {
    const result = await super.open('Dashboard_Dashboard.1');

    await $('#dashboardWidgetsArea .widgetContent div').waitForDisplayed();
    await browser.waitUntil(async () => {
      await browser.pause(500);

      const elements = (await $$('.loadingPiwik'));

      const visibleElements = [];
      for (let e of elements) {
        if (await e.isDisplayed()) {
          visibleElements.push(e);
        }
      }

      console.log('elements', elements.length, 'visibleElements', visibleElements.length);

      return visibleElements.length === 0;
    });

    return result;
  }
}

export default new DashboardPage();
