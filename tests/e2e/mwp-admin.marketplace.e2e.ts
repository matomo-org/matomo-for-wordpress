/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { expect, browser } from '@wdio/globals';
import MwpMarketplacePage from './pageobjects/mwp-admin/marketplace.page.js';
import Website from './website.js';

describe('MWP Admin > Marketplace', () => {
  const trunkSuffix = process.env.WORDPRESS_VERSION === 'trunk' ? '.trunk' : '';

  before(async () => {
    await Website.login();
  });

  it('should load the overview tab correctly', async () => {
    await MwpMarketplacePage.open();

    await MwpMarketplacePage.prepareWpAdminForScreenshot();
    await expect(
      await browser.checkFullPageScreen(`mwp-admin.marketplace.overview${trunkSuffix}`)
    ).toEqual(0);
  });

  it('should load the install plugins tab correctly', async () => {
    await MwpMarketplacePage.open();
    await MwpMarketplacePage.openInstallPluginsTab();

    await MwpMarketplacePage.prepareWpAdminForScreenshot();
    await expect(
      await browser.checkFullPageScreen(`mwp-admin.marketplace.install-plugins${trunkSuffix}`)
    ).toEqual(0);
  });

  it('should load the subscriptions tab correctly', async () => {
    await MwpMarketplacePage.open();
    await MwpMarketplacePage.openSubscriptionsTab();

    await MwpMarketplacePage.prepareWpAdminForScreenshot();
    await expect(
      await browser.checkFullPageScreen(`mwp-admin.marketplace.subscriptions${trunkSuffix}`)
    ).toEqual(0);
  });
});
