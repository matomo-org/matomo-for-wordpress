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
    try {
      await $('#dashboardWidgetsArea .widgetContent div').waitForDisplayed({ timeout: 120000 });
    } catch (e) {
      // ignore (fails randomly sometimes, unsure why)
    }

    await browser.waitUntil(async () => {
      const widgetsCount = (await $$('#dashboardWidgetsArea .widget')).length;
      const loadedWidgetCount = (await $$('#dashboardWidgetsArea .widgetContent > *:first-child:not(.widgetLoading)')).length;

      return loadedWidgetCount >= widgetsCount;
    }, { timeout: 60000 });
    await this.waitForVisitorMap();
    await browser.execute(function () {
      $('.widget ul.rss').hide();
    });
    await this.addStylesToPage('#visitsLive .realTimeWidget_datetime { display: none !important; }');

    // the live widget's vue component (with the pause button) can mount after the widget is marked loaded
    await browser.waitUntil(() => browser.execute(() => {
      if (!$('[widgetId="widgetLivewidget"]').length) {
        return true;
      }

      const pauseImage = document.querySelector('[widgetId="widgetLivewidget"] #pauseImage') as HTMLImageElement|null;
      return !!pauseImage && pauseImage.complete && pauseImage.naturalWidth > 0;
    }), { timeout: 30000 });

    await this.waitForImages();
  }

  async waitForVisitorMap() {
    let readyChecks = 0;
    await browser.waitUntil(async () => {
      const isReady = await browser.execute(() => {
        if ($('.mapWidgetStatus .pk-emptyDataTable:visible').length) {
          return true;
        }

        return $('.UserCountryMap_map svg').length > 0
          && $('.UserCountryMap .map-stats').text().trim() !== ''
          && $('.UserCountryMap .loadingPiwik:visible,.UserCountryMap-black:visible').length === 0
          && $('.UserCountryMap :animated').length === 0
          && window.jQuery.active === 0;
      });
      readyChecks = isReady ? readyChecks + 1 : 0;
      return readyChecks >= 3;
    }, { timeout: 60000, interval: 200 });
  }

  async normalizeDates() {
    await browser.execute(() => {
      $('#periodString #date').text('REMOVED');
      $('#widgetVisitsSummarygetEvolutionGraphforceView1viewDataTablegraphEvolution .jqplot-xaxis').hide();
    });
  }
}

export default new DashboardPage();
