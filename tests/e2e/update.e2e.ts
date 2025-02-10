/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { browser, $, expect } from '@wdio/globals';
import fetch from 'node-fetch';
import Website from './website.js';
import MatomoCli from './apiobjects/matomo.cli.js';
import GdprToolsPage from './pageobjects/mwp-admin/about.page.js';

describe('MWP Updating', () => {
  const trunkSuffix = process.env.WORDPRESS_VERSION === 'trunk' ? '.trunk' : '';

  before(async () => {
    await Website.login();
  });

  // sanity check to make sure we are updating from the latest stable version
  it('should have the latest stable version installed', async () => {
    const pluginInfo = await(await fetch('https://api.wordpress.org/plugins/info/1.0/matomo.json')).json();
    const latestStableVersion = pluginInfo.version as string;

    await browser.url(`${await Website.baseUrl()}/wp-admin/plugins.php`);
    await $('tr[data-slug="matomo"]').waitForDisplayed({ timeout: 30000 });

    const actualVersion = await browser.execute(() => {
      const [, v] = window.jQuery('tr[data-slug="matomo"] .plugin-version-author-uri').text().match(/Version (\d+\.\d+\.\d+)/);
      return v;
    });

    expect(actualVersion).toEqual(latestStableVersion);
  });

  it('should succeed when updating to the current code', async () => {
    await Website.updateMatomoToLatest();
  });

  it('should display whats new notifications on install', async () => {
    await GdprToolsPage.open();

    await GdprToolsPage.prepareWpAdminForScreenshot();
    await expect(
      await browser.checkFullPageScreen(`mwp-admin.whats-new-notifications.${process.env.PHP_VERSION}${trunkSuffix}`)
    ).toEqual(0);
  });

  it('should permanently hide whats new notifications on dismissal', async () => {
    await browser.execute(() => {
      window.jQuery('.matomo-whats-new .notice-dismiss').each(function () {
        this.click();
      });
    });

    await browser.waitUntil(async () => {
      return await browser.execute(() => window.jQuery('.matomo-whats-new').length) === 0;
    }, { timeout: 30000 });

    await browser.pause(1000); // additional wait for ajax methods to complete
  });
});
