/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { expect, browser } from '@wdio/globals';
import MwpSummaryPage from './pageobjects/mwp-admin/summary.page.js';
import Website from './website.js';

describe('MWP Admin > Summary', () => {
  before(async () => {
    await Website.login();
  });

  it('should load the summary page correctly', async () => {
    await MwpSummaryPage.open();

    await MwpSummaryPage.prepareWpAdminForScreenshot();
    await expect(
      await browser.checkFullPageScreen('mwp-admin.summary')
    ).toBeLessThan(0.01);
  });

  it('should change the date correctly when a period button is toggled', async () => {
    await MwpSummaryPage.changePeriod('This month');

    await MwpSummaryPage.prepareWpAdminForScreenshot();
    await expect(
      await browser.checkFullPageScreen('mwp-admin.summary.thismonth')
    ).toBeLessThan(0.01);
  });

  // TODO: add test for report pinning
});
