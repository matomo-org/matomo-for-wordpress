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
    await browser.pause(500);
    await this.waitForEvolutionAnnotations();
    await this.waitForImages();

    // hide video thumbnail since it renders differently randomly
    await browser.execute(() => {
      window.jQuery('#piwik-promo-thumbnail').hide();
    });

    // live visits widget always renders differently so hide it. it's not too important
    // to test this in MWP.
    await this.addStylesToPage('#visitsLive { display: none !important; }');

    await this.unfocus();

    return result;
  }

  async waitForEvolutionAnnotations() {
    await browser.waitUntil(async () => {
      const isEvolutionAnnotationsLoaded = await browser.execute(
        () => $('.dataTableVizEvolution,.dataTableVizStackedBarEvolution').length === $('.evolution-annotations').length
      );

      return isEvolutionAnnotationsLoaded;
    }, { timeout: 30000 });
  }

  async waitForPageWidgets() {
    let numWidgetsLoaded = 0;
    let numLoadingsFound = 0;

    try {
      await browser.waitUntil(async () => {
        const loadings = await $$('.matomo-widget > div > .loadingPiwik,.matomo-widget .dimensionReport > .loadingPiwik');

        numLoadingsFound = loadings.length;

        numWidgetsLoaded = 0;
        for (const loading of loadings) {
          if (!(await loading.isDisplayed())
            && (await loading.isExisting())
          ) {
            numWidgetsLoaded += 1;
          }
        }

        console.log('waiting', numLoadingsFound, numWidgetsLoaded, numLoadingsFound > 0, numLoadingsFound === numWidgetsLoaded);
        return numLoadingsFound > 0 && numLoadingsFound === numWidgetsLoaded;
      }, { timeout: 90000 });
    } catch (e) {
      console.log('numLoadingsFound > 0: ', numLoadingsFound > 0);
      console.log('numLoadingsFound === numWidgetsLoaded', numLoadingsFound === numWidgetsLoaded);
      console.log(`waitForPageWidgets failed, numLoadingsFound = ${numLoadingsFound}, numWidgetsLoaded = ${numWidgetsLoaded}`);
      throw e;
    }
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
    }, { timeout: 30000 });
  }

  getDefaultDate() {
    return GlobalSetup.getDateOfVisitTrackedInPast(); // use a fixed date instead of today/yesterday
  }
}
