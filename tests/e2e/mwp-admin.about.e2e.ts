/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { expect, browser } from '@wdio/globals';
import MwpAboutPage from './pageobjects/mwp-admin/about.page.js';
import Website from './website.js';

describe('MWP Admin > About', () => {
  const trunkSuffix = process.env.WORDPRESS_VERSION === 'trunk' ? '.trunk' : '';

  before(async () => {
    if (!process.env.PHP_VERSION) {
      throw new Error('Unexpected: PHP_VERSION environment variable cannot be found.');
    }

    await Website.login();
  });

  it('should load the page correctly', async () => {
    await MwpAboutPage.open();

    await MwpAboutPage.prepareWpAdminForScreenshot();
    await expect(
      await browser.checkFullPageScreen(`mwp-admin.about.${process.env.PHP_VERSION}${trunkSuffix}`)
    ).toEqual(0);
  });
});
