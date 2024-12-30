/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { expect, browser } from '@wdio/globals';
import Website from './website.js';
import GetStartedPage from './pageobjects/mwp-admin/get-started.page.js';
import SettingsPage from './pageobjects/mwp-admin/settings.page.js';

describe('MultiSite General', function() {
  const trunkSuffix = process.env.WORDPRESS_VERSION === 'trunk' ? '.trunk' : '';

  before(async () => {
    if (!process.env.PHP_VERSION) {
      throw new Error('Unexpected: PHP_VERSION environment variable cannot be found.');
    }

    Website.overrideWordPressFolder(`${await Website.getWpFolder()}-multi`);
    await Website.login();
  });

  after(async () => {
    Website.unsetSite();
    Website.removeWordPressFolderOverride();
  });

  it('should display the MWP admin pages for a single site correctly', async () => {
    Website.switchSite('test2');

    await GetStartedPage.open();

    await GetStartedPage.prepareWpAdminForScreenshot();
    await expect(
      await browser.checkFullPageScreen(`mwp-admin.multisite.get-started.${process.env.PHP_VERSION}${trunkSuffix}`)
    ).toBeLessThan(0.01);
  });

  it('should display the MWP settings page for a single site correctly when MWP is network enabled', async () => {
    await SettingsPage.open();

    await SettingsPage.prepareWpAdminForScreenshot();
    await expect(
      await browser.checkFullPageScreen(`mwp-admin.multisite.settings.${process.env.PHP_VERSION}${trunkSuffix}`)
    ).toBeLessThan(0.01);
  });

  it('should display the Matomo reporting pages for a single site correctly', async () => {
    await browser.refresh();

    await $('#toplevel_page_matomo a').waitForExist();

    await browser.execute(() => {
      window.jQuery('#toplevel_page_matomo a[href*="matomo-reporting"]')[0].click();
    });

    await expect(
      await browser.checkFullPageScreen(`mwp-admin.multisite.mtm-reporting.${process.env.PHP_VERSION}${trunkSuffix}`)
    ).toBeLessThan(0.01);
  });
});
