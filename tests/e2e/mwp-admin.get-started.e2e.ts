/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { expect, browser, $ } from '@wdio/globals';
import fetch from 'node-fetch';
import MwpGetStartedPage from './pageobjects/mwp-admin/get-started.page.js';
import Website from './website.js';
import OverviewPage from './pageobjects/matomo-reporting/visitors/overview.page.js';

describe('MWP Admin > Get Started', () => {
  const trunkSuffix = process.env.WORDPRESS_VERSION === 'trunk' ? '.trunk' : '';

  before(async () => {
    if (!process.env.PHP_VERSION) { // TODO: check this in wdio.conf
      throw new Error('Unexpected: PHP_VERSION environment variable cannot be found.');
    }

    await Website.login();

    await fetch(`${await Website.baseUrl()}/wp-admin/admin-ajax.php`, {
      method: 'POST',
      headers:{
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      body: new URLSearchParams({
        action: 'matomo_test_show_get_started',
        date: OverviewPage.getDefaultDate(),
      }),
    });
  });

  it('should load the page correctly', async () => {
    await MwpGetStartedPage.open();

    await MwpGetStartedPage.prepareWpAdminForScreenshot();
    await expect(
      await browser.checkFullPageScreen(`mwp-admin.get-started.${process.env.PHP_VERSION}${trunkSuffix}`)
    ).toEqual(0);
  });

  it('should hide the page when both steps are completed', async () => {
    await MwpGetStartedPage.enableTracking();
    expect(await $('.matomo-dashboard-container').isExisting()).toBeTruthy();
  });
});
