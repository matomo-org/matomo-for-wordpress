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
  const trunkSuffix = process.env.WORDPRESS_VERSION === 'trunk' ? '.trunk' : '';

  before(async () => {
    await Website.login();
  });

  it('should load the summary page correctly', async () => {
    await MwpSummaryPage.open();

    await MwpSummaryPage.prepareWpAdminForScreenshot();
    await expect(
      await browser.checkFullPageScreen(`mwp-admin.summary${trunkSuffix}`)
    ).toBeLessThan(0.01);
  });

  it('should change the date correctly when a period button is toggled', async () => {
    await MwpSummaryPage.changePeriod('This month');

    await MwpSummaryPage.prepareWpAdminForScreenshot();
    await expect(
      await browser.checkFullPageScreen(`mwp-admin.summary.thismonth${trunkSuffix}`)
    ).toBeLessThan(0.01);
  });

  it('should pin reports to the WordPress dashboard when the pin icon is clicked', async () => {
    await MwpSummaryPage.pinReport(0);

    await browser.url(`${await Website.baseUrl()}/wp-admin/index.php`);
    await $('#matomo_dashboard_widget_visits_over_time_thismonth').waitForDisplayed();
  });
});
