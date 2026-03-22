/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { expect, browser } from '@wdio/globals'
import Website from './website.js';
import NetworkMultiSitePage from './pageobjects/mwp-network-admin/multisite.page.js';
import NetworkSettingsPage from './pageobjects/mwp-network-admin/settings.page.js';
import NetworkDiagnosticsPage from './pageobjects/mwp-network-admin/diagnostics.page.js';
import NetworkHelpPage from './pageobjects/mwp-network-admin/help.page.js';
import NetworkMarketplacePage from './pageobjects/mwp-network-admin/marketplace.page.js';
import GetStartedPage from "./pageobjects/mwp-admin/get-started.page";

describe('Network Admin', function() {
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

  it('should display the multisite get started page correctly', async () => {
    // for some reason on the first load, the app/bootstrap.php cannot be found
    // TODO: sometimes there's a random failure here where the browser gets redirected to the login page. unsure why this happens.
    await Website.retry(3, async () => {
      await NetworkMultiSitePage.open();
    }, 3000);

    await NetworkMultiSitePage.prepareWpAdminForScreenshot();
    await expect(
      await browser.checkFullPageScreen(`mwp-admin.network.multisite.${process.env.PHP_VERSION}${trunkSuffix}`)
    ).toBeLessThan(0.01);
  });

  it('should display the multisite settings page correctly', async () => {
    await NetworkSettingsPage.open();

    await NetworkSettingsPage.prepareWpAdminForScreenshot();
    await expect(
      await browser.checkFullPageScreen(`mwp-admin.network.settings.${process.env.PHP_VERSION}${trunkSuffix}`)
    ).toBeLessThan(0.01);
  });

  it('should display the multisite diagnostics page correctly', async () => {
    await NetworkDiagnosticsPage.open();

    await NetworkDiagnosticsPage.prepareWpAdminForScreenshot();
    await expect(
      await browser.checkFullPageScreen(`mwp-admin.network.diagnostics.${process.env.PHP_VERSION}${trunkSuffix}`)
    ).toBeLessThan(0.03);
  });

  it('should display the multisite help page correctly', async () => {
    await NetworkHelpPage.open();

    await NetworkHelpPage.prepareWpAdminForScreenshot();
    await expect(
      await browser.checkFullPageScreen(`mwp-admin.network.help.${process.env.PHP_VERSION}${trunkSuffix}`)
    ).toBeLessThan(0.01);
  });

  it('should display the multisite marketplace page correctly', async () => {
    await NetworkMarketplacePage.open();

    await NetworkMarketplacePage.prepareWpAdminForScreenshot();
    await expect(
      await browser.checkFullPageScreen(`mwp-admin.network.marketplace.${process.env.PHP_VERSION}${trunkSuffix}`)
    ).toBeLessThan(0.01);
  });
});
