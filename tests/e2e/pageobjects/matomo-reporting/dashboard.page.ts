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
      const widgetsCount = (await $$('#dashboardWidgetsArea .widget')).length;
      const loadedWidgetCount = (await $$('#dashboardWidgetsArea .theWidgetContent > *')).length;

      console.log('widgetsCount', widgetsCount, 'loadedWidgetCOunt', loadedWidgetCount);

      return loadedWidgetCount >= widgetsCount;
    });

    return result;
  }
}

export default new DashboardPage();
