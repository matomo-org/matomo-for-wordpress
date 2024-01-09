/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { browser, $ } from '@wdio/globals';
import * as querystring from 'querystring';
import MatomoPage from './matomo.page.js';
import GlobalSetup from '../global-setup.js';

export default class MatomoReportingPage extends MatomoPage {
  async open(categorySubcategory: string, params: Record<string, string> = {}) {
    const [category, subcategory] = categorySubcategory.split('.');

    const query = querystring.stringify({
      idSite: 1,
      period: 'day',
      date: this.getDefaultDate(),
      module: 'CoreHome',
      action: 'index',
    });

    const hashQuery = querystring.stringify({
      period: 'day',
      date: this.getDefaultDate(),
      category,
      subcategory,
      ...params,
    });

    const result = await super.open(`/wp-content/plugins/matomo/app/index.php?${query}#?${hashQuery}`);

    await this.waitForPageWidgets();
    await this.waitForImages();

    await this.unfocus();

    return result;
  }

  async waitForPageWidgets() {
    await browser.waitUntil(async () => {
      const loadings = await $$('.matomo-widget > div > .loadingPiwik,.matomo-widget .dimensionReport > .loadingPiwik');

      const isThereWidgets = loadings.length > 0;

      let numWidgetsLoaded = 0;
      for (const loading of loadings) {
        if (!(await loading.isDisplayed())
          && (await loading.isExisting())
        ) {
          numWidgetsLoaded += 1;
        }
      }

      return isThereWidgets && loadings.length === numWidgetsLoaded;
    }, { timeout: 20000 });
  }

  async waitForActionsTables() {
    await browser.waitUntil(async () => {
      await browser.pause(500);

      const rowLoadings = await $$('td .loadingPiwik');
      if (rowLoadings.length === 0) {
        return true;
      }

      let isAnyDisplayed = false;
      for (const loading of rowLoadings) {
        isAnyDisplayed = isAnyDisplayed || (await loading.isDisplayed());
      }

      if (!isAnyDisplayed) {
        return true;
      }

      return false;
    });
  }

  getDefaultDate() {
    return GlobalSetup.getDateOfVisitTrackedInPast(); // use a fixed date instead of today/yesterday
  }
}
