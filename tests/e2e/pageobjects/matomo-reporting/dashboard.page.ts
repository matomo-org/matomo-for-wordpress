/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { $, $$, browser } from '@wdio/globals';
import MatomoReportingPage from '../matomo-reporting.page.js';

class DashboardPage extends MatomoReportingPage {
  async open() {
    const result = await super.open('Dashboard_Dashboard.1');
    await this.waitForDashboard();
    await this.hidePromoThumbnail();
    return result;
  }

  async hidePromoThumbnail() {
    // promo video thumbnail does not render consistently
    await browser.execute(() => {
      $('#piwik-promo-video').hide();
    });
  }

  async waitForDashboard() {
    await $('#dashboardWidgetsArea .widgetContent div').waitForDisplayed({ timeout: 120000 });
    await browser.waitUntil(async () => {
      const widgetsCount = (await $$('#dashboardWidgetsArea .widget')).length;
      const loadedWidgetCount = (await $$('#dashboardWidgetsArea .widgetContent > *:first-child:not(.widgetLoading)')).length;

      return loadedWidgetCount >= widgetsCount;
    }, { timeout: 60000 });
    await browser.waitUntil(async () => {
      return await $('.UserCountryMap_map.kartograph,.mapWidgetStatus .pk-emptyDataTable').isDisplayed();
    }, { timeout: 60000 });
    await browser.execute(function () {
      $('.widget ul.rss').hide();
    });
    await this.addStylesToPage('#visitsLive .realTimeWidget_datetime { display: none !important; }');
    await this.waitForImages();
  }

  async normalizeDates() {
    await browser.execute(() => {
      $('#periodString #date').text('REMOVED');
      $('#widgetVisitsSummarygetEvolutionGraphforceView1viewDataTablegraphEvolution .jqplot-xaxis').hide();
    });
  }
}

export default new DashboardPage();
