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
import SummaryPage from './pageobjects/mwp-admin/summary.page.js';

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
    await $('tr[data-slug="matomo"]').waitForDisplayed();

    const actualVersion = await browser.execute(() => {
      const [, v] = window.jQuery('tr[data-slug="matomo"] .plugin-version-author-uri').text().match(/Version (\d+\.\d+\.\d+)/);
      return v;
    });

    expect(actualVersion).toEqual(latestStableVersion);
  });

  it('should succeed when updating to the current code', async () => {
    const pathToRelease = process.env.RELEASE_ZIP || MatomoCli.buildRelease();

    await browser.url(`${await Website.baseUrl()}/wp-admin/plugin-install.php`);
    await $('a.upload-view-toggle').waitForDisplayed();

    await $('a.upload-view-toggle').click();
    await $('#pluginzip').setValue(pathToRelease);
    await browser.pause(250);

    await $('#install-plugin-submit').waitForClickable();
    await $('#install-plugin-submit').click();

    await $('.update-from-upload-overwrite').waitForDisplayed();
    await browser.execute(() => {
      window.jQuery('.update-from-upload-overwrite')[0].click();
    });

    await browser.waitUntil(async () => {
      return await browser.execute(() => {
        return window.jQuery && window.jQuery('p:contains(Plugin updated successfully.)').length > 0;
      });
    }, { timeout: 120000 });

    await SummaryPage.open();

    await SummaryPage.prepareWpAdminForScreenshot();
    await expect(
      await browser.checkFullPageScreen(`mwp-update.summary-after-update${trunkSuffix}`)
    ).toEqual(0);
  });
});
