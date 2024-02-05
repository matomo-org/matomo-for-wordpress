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

    await browser.execute(function () {
      $('.tourChallenge .icon-ok,.tourChallenge .icon-hide').hide();
      $('.tourEngagement p').each((i, e) => {
        if (/Complete \d+/.test($(e).text())) {
          $(e).text($(e).text().replace(/Complete \d+/g, 'Complete '));
        }

        // this text can be different based on what e2e test ends up running
        // first, so just hide it
        if (/a matomo expert/i.test($(e).text())) {
          $(e).text('REMOVED BY TEST');
        }
      });
    });

    await MatomoDashboardPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo-reporting.dashboard')
    ).toBeLessThan(0.05);
  });
});
