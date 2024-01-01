/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { expect, browser } from '@wdio/globals';
import MatomoDashboardPage from './pageobjects/matomo-reporting/dashboard.page.js';
import Website from './website.js';

describe('Matomo Reporting > Dashboard', () => {
  before(async () => {
    await Website.login();
  });

  it('should load the dashboard page correctly', async () => {
    await MatomoDashboardPage.open();

    await expect(
      await browser.checkFullPageScreen('matomo-reporting.dashboard')
    ).toEqual(0);
  });
});