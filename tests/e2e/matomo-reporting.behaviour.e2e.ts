/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { expect, browser } from '@wdio/globals';
import ContentsPage from './pageobjects/matomo-reporting/behaviour/contents.page.js';
import DownloadsPage from './pageobjects/matomo-reporting/behaviour/downloads.page.js';
import EngagementPage from './pageobjects/matomo-reporting/behaviour/engagement.page.js';
import EntryPagesPage from './pageobjects/matomo-reporting/behaviour/entry-pages.page.js';
import EventsPage from './pageobjects/matomo-reporting/behaviour/events.page.js';
import ExitPagesPage from './pageobjects/matomo-reporting/behaviour/exit-pages.page.js';
import OutlinksPage from './pageobjects/matomo-reporting/behaviour/outlinks.page.js';
import PageTitlesPage from './pageobjects/matomo-reporting/behaviour/page-titles.page.js';
import PagesPage from './pageobjects/matomo-reporting/behaviour/pages.page.js';
import PerformancePage from './pageobjects/matomo-reporting/behaviour/performance.page.js';
import SiteSearchPage from './pageobjects/matomo-reporting/behaviour/site-search.page.js';
import TransitionsPage from './pageobjects/matomo-reporting/behaviour/transitions.page.js';
import Website from './website.js';

describe('Matomo Reporting > Behaviour', () => {
  before(async () => {
    await Website.login();
  });

  it('should load the contents page correctly', async () => {
    await ContentsPage.open();

    await ContentsPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo-reporting.behaviour.contents')
    ).toBeLessThan(0.1);
  });

  it('should load the downloads page correctly', async () => {
    await DownloadsPage.open();

    await DownloadsPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo-reporting.behaviour.downloads')
    ).toBeLessThan(0.1);
  });

  it('should load the engagement page correctly', async () => {
    await EngagementPage.open();

    // row positions in tag clouds can change randomly when the primary metric
    // has the samevalue for each row. so we change tag clouds to simple tables
    // before taking a screenshot.
    const widgetsToChange = [
      'widgetVisitorInterestgetNumberOfVisitsPerVisitDuration',
      'widgetVisitorInterestgetNumberOfVisitsPerPage',
    ];

    for (const w of widgetsToChange) {
      await browser.execute((widget) => {
        $(`#${widget} .activateVisualizationSelection`)[0].click();
      }, w);
      await browser.pause(250);
      await browser.execute((widget) => {
        $(`#${widget} .tableAllColumnsSwitch`)[0].click();
      }, w);
    }

    await browser.pause(2000);

    await EngagementPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo-reporting.behaviour.engagement')
    ).toBeLessThan(0.1);
  });

  it('should load the entry pages page correctly', async () => {
    await EntryPagesPage.open();

    await EntryPagesPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo-reporting.behaviour.entry-pages')
    ).toBeLessThan(0.1);
  });

  it('should load the  page correctly', async () => {
    await EventsPage.open();

    await EventsPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo-reporting.behaviour.events')
    ).toBeLessThan(0.1);
  });

  it('should load the exit pages page correctly', async () => {
    await ExitPagesPage.open();

    await ExitPagesPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo-reporting.behaviour.exit-pages')
    ).toBeLessThan(0.1);
  });

  it('should load the outlinks page correctly', async () => {
    await OutlinksPage.open();

    await OutlinksPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo-reporting.behaviour.outlinks')
    ).toBeLessThan(0.1);
  });

  it('should load the page titles page correctly', async () => {
    await PageTitlesPage.open();

    await PageTitlesPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo-reporting.behaviour.page-titles')
    ).toBeLessThan(0.1);
  });

  it('should load the pages page correctly', async () => {
    await PagesPage.open();

    await PagesPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo-reporting.behaviour.pages')
    ).toBeLessThan(0.1);
  });

  it('should load the performance page correctly', async () => {
    await PerformancePage.open();

    await PerformancePage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo-reporting.behaviour.performance')
    ).toBeLessThan(0.1);
  });

  it('should load the site search page correctly', async () => {
    await SiteSearchPage.open();

    await SiteSearchPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo-reporting.behaviour.site-search')
    ).toBeLessThan(0.1);
  });

  it('should load the transitions page correctly', async () => {
    await TransitionsPage.open();

    await TransitionsPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo-reporting.behaviour.transitions')
    ).toBeLessThan(0.1);
  });
});
