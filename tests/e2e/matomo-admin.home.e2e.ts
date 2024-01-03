/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { expect, browser } from '@wdio/globals';
import MatomoHomePage from './pageobjects/matomo-admin/home.page.js';
import Website from './website.js';

describe('Matomo Admin > Home', () => {
  before(async () => {
    await Website.login();
  });

  it('should load the home page correctly', async () => {
    await MatomoHomePage.open();

    await browser.execute(function () {
      $('ul.rss').hide();
    });

    await expect(
      await browser.checkFullPageScreen('matomo-admin.home')
    ).toEqual(0);
  });
});
