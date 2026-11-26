/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { expect, browser, $ } from '@wdio/globals';
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
import { getScreenshotName, getPhp74TrunkScreenshotName } from './screenshot.js';

describe('Matomo Reporting > Behaviour', () => {
  before(async () => {
    await Website.login();
  });

  it('should load the contents page correctly', async () => {
    await ContentsPage.open();

    await ContentsPage.prepareMatomoPageForScreenshot();
    await expect(
      await browser.checkFullPageScreen(getPhp74TrunkScreenshotName('matomo-reporting.behaviour.contents'))
    ).toBeLessThan(0.1);
  });

  it('should load the downloads page correctly', async () => {
    await DownloadsPage.open();

    await DownloadsPage.prepareMatomoPageForScreenshot();
    await expect(
      await browser.checkFullPageScreen('matomo-reporting.behaviour.downloads')
    ).toBeLessThan(0.1);
  });

  it('should load the engagement page correctly', async () => {
    // Request tables through the report URL instead of depending on the report-actions menu.
    // Equal-frequency tags can appear in a different order on each tag-cloud render.
    await EngagementPage.open({ viewDataTable: 'table' });

    const widgetsToCheck = [
      'widgetVisitorInterestgetNumberOfVisitsPerVisitDuration',
      'widgetVisitorInterestgetNumberOfVisitsPerPage',
    ];

    for (const id of widgetsToCheck) {
      const widget = $(`#${id}`);
      await expect(widget.$('table.dataTable')).toBeDisplayed({ wait: 30000 });
      await expect(widget.$('table.dataTable tbody tr')).toBeDisplayed();
      await expect(widget.$('.tagCloud')).not.toBeDisplayed();
    }
  });

  it('should load the entry pages page correctly', async () => {
    await EntryPagesPage.open();

    await EntryPagesPage.prepareMatomoPageForScreenshot();
    await expect(
      await browser.checkFullPageScreen('matomo-reporting.behaviour.entry-pages')
    ).toBeLessThan(0.1);
  });

  it('should load the  page correctly', async () => {
    await EventsPage.open();

    await EventsPage.prepareMatomoPageForScreenshot();
    await expect(
      await browser.checkFullPageScreen('matomo-reporting.behaviour.events')
    ).toBeLessThan(0.1);
  });

  it('should load the exit pages page correctly', async () => {
    await ExitPagesPage.open();

    await ExitPagesPage.prepareMatomoPageForScreenshot();
    await expect(
      await browser.checkFullPageScreen('matomo-reporting.behaviour.exit-pages')
    ).toBeLessThan(0.1);
  });

  it('should load the outlinks page correctly', async () => {
    await OutlinksPage.open();

    await OutlinksPage.prepareMatomoPageForScreenshot();
    await expect(
      await browser.checkFullPageScreen('matomo-reporting.behaviour.outlinks')
    ).toBeLessThan(0.1);
  });

  it('should load the page titles page correctly', async () => {
    await PageTitlesPage.open();

    await PageTitlesPage.prepareMatomoPageForScreenshot();
    await expect(
      await browser.checkFullPageScreen('matomo-reporting.behaviour.page-titles')
    ).toBeLessThan(0.1);
  });

  it('should load the pages page correctly', async () => {
    await PagesPage.open();

    await PagesPage.prepareMatomoPageForScreenshot();
    await expect(
      await browser.checkFullPageScreen('matomo-reporting.behaviour.pages')
    ).toBeLessThan(0.1);
  });

  it('should load the performance page correctly', async () => {
    await PerformancePage.open();

    await PerformancePage.prepareMatomoPageForScreenshot();
    await expect(
      await browser.checkFullPageScreen(getScreenshotName('matomo-reporting.behaviour.performance'))
    ).toBeLessThan(0.1);
  });

  it('should load the site search page correctly', async () => {
    await SiteSearchPage.open();

    await SiteSearchPage.prepareMatomoPageForScreenshot();
    await expect(
      await browser.checkFullPageScreen('matomo-reporting.behaviour.site-search')
    ).toBeLessThan(0.1);
  });

  it('should load the transitions page correctly', async () => {
    await TransitionsPage.open();

    await TransitionsPage.prepareMatomoPageForScreenshot();
    await expect(
      await browser.checkFullPageScreen('matomo-reporting.behaviour.transitions')
    ).toBeLessThan(0.1);
  });
});
