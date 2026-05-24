/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { expect, browser } from '@wdio/globals';
import * as path from 'path';
import { existsSync } from 'node:fs';
import { readdir, rm } from 'node:fs/promises';
import MwpMarketplacePage from './pageobjects/mwp-admin/marketplace.page.js';
import Website from './website.js';
import GlobalSetup from './global-setup.js';
import SummaryPage from './pageobjects/mwp-admin/summary.page.js';
import MatomoPromoPage from './pageobjects/matomo-reporting/promo.page.js';

describe('MWP Admin > Marketplace', () => {
  const trunkSuffix = process.env.WORDPRESS_VERSION === 'trunk' ? '.trunk' : '';

  async function deleteAllMarketplacePlugins() {
    const wpPluginsToDelete = [
      'matomo-marketplace-for-wordpress',
    ];

    const pathToWordPress = path.join('docker', 'wordpress', await Website.getWpFolder(), 'wp-content', 'plugins');
    const dirs = await readdir(pathToWordPress);
    for (let dir of dirs) {
      const pluginJson = path.join(pathToWordPress, dir, 'plugin.json');
      if (existsSync(pluginJson)) {
        wpPluginsToDelete.push(dir);
      }
    }

    for (let plugin of wpPluginsToDelete) {
      await rm(path.join(pathToWordPress, plugin), { recursive: true, force: true });
    }
  }

  before(async () => {
    if (!process.env.PHP_VERSION) {
      throw new Error('Unexpected: PHP_VERSION environment variable cannot be found.');
    }

    await GlobalSetup.setUp();
    await deleteAllMarketplacePlugins();
    await Website.login();
  });

  after(async () => {
    await SummaryPage.open(); // open matomo page to trigger any pending updates
  });

  it('should load the overview tab correctly', async () => {
    await MwpMarketplacePage.open();

    await MwpMarketplacePage.prepareWpAdminForScreenshot();
    await expect(
      await browser.checkFullPageScreen(`mwp-admin.marketplace.overview.${process.env.PHP_VERSION}${trunkSuffix}`)
    ).toEqual(0);
  });

  it('should show the marketplace setup wizard when the marketplace plugins is not installed', async () => {
    await browser.refresh();
    await MwpMarketplacePage.openMarketplacePluginsTab();

    await MwpMarketplacePage.prepareWpAdminForScreenshot();
    await expect(
      await browser.checkFullPageScreen(`mwp-admin.marketplace.setup-wizard.${process.env.PHP_VERSION}${trunkSuffix}`)
    ).toEqual(0);
  });

  it('should provide functionality that simplifies the process of downloading and installing the plugin', async () => {
    await MwpMarketplacePage.open();

    // TODO: see if this still applies
    const pathToPlugin = await MwpMarketplacePage.setupWizard.downloadPlugin();
    await MwpMarketplacePage.setupWizard.goToPluginsAdmin();
    await MwpMarketplacePage.setupWizard.uploadPluginAndActivate(pathToPlugin);
    await MwpMarketplacePage.setupWizard.waitForReload();

    await MwpMarketplacePage.sortPluginsAlphabetically();
    await MwpMarketplacePage.removeThirdPartyPlugins();
    await MwpMarketplacePage.prepareWpAdminForScreenshot();
    await expect(
      await browser.checkFullPageScreen(`mwp-admin.marketplace.setup-wizard-finished.${process.env.PHP_VERSION}${trunkSuffix}`)
    ).toEqual(0);
  });

  const PROMOS = [
    'Funnels',
    'Heatmaps',
    'SessionRecording',
    'CrashAnalytics',
    'CustomReports',
    'MediaAnalytics',
    'FormAnalytics',
  ];
  PROMOS.forEach((promo) => {
    it(`should display the ${promo} promo correctly`, async () => {
      await MatomoPromoPage.open(promo);

      const plugin = promo === 'Heatmaps' || promo === 'SessionRecording' ? 'HeatmapSessionRecording' : promo;

      // no screenshot testing since we depend on what is in core
      expect(await $('.pluginPromo').isExisting()).toBeTruthy();

      const unlockUrl = await browser.execute(() => $('.pluginPromo .promo-actions a:not(.learn-more)').attr('href'));
      expect(unlockUrl).toEqual(`https://plugins.matomo.org/${plugin}?add-to-cart=ws&currency=EUR&wp=1`);

      const learnMoreUrl = await browser.execute(() => $('.pluginPromo .promo-actions a.learn-more').attr('href'));
      expect(learnMoreUrl).toEqual(`https://plugins.matomo.org/${plugin}?currency=EUR&wp=1`);
    });
  });

  it('should dismiss the promo when the hide link is clicked', async () => {
    await MatomoPromoPage.open('Funnels');
    await MatomoPromoPage.dismiss();

    expect(await $('.reportingMenu .menuTab').isExisting()).toBeTruthy();
    expect(await $('.menuTab[data-category-id="ProfessionalServices_PromoFunnels"]').isExisting()).toBeFalsy();
  });

  it('should load the overview tab correctly when the marketplace plugin is installed', async () => {
    await MwpMarketplacePage.open();

    await MwpMarketplacePage.prepareWpAdminForScreenshot();
    await expect(
      await browser.checkFullPageScreen(`mwp-admin.marketplace.overview-after-install.${process.env.PHP_VERSION}${trunkSuffix}`)
    ).toEqual(0);
  });

  it('should load the install plugins tab correctly', async () => {
    await browser.refresh();
    await MwpMarketplacePage.openMarketplacePluginsTab();

    await MwpMarketplacePage.prepareWpAdminForScreenshot();
    await expect(
      await browser.checkFullPageScreen(`mwp-admin.marketplace.install-plugins.${process.env.PHP_VERSION}${trunkSuffix}`)
    ).toEqual(0);
  });

  it('should load the subscriptions tab correctly', async () => {
    await browser.refresh();
    await MwpMarketplacePage.openSubscriptionsTab();

    await MwpMarketplacePage.prepareWpAdminForScreenshot();
    await expect(
      await browser.checkFullPageScreen(`mwp-admin.marketplace.subscriptions.${process.env.PHP_VERSION}${trunkSuffix}`)
    ).toEqual(0);
  });

  it('should save a subscription license', async () => {
    await browser.refresh();
    await MwpMarketplacePage.setSubscriptionLicense(process.env.TEST_SHOP_LICENSE);
    await MwpMarketplacePage.openSubscriptionsTab();

    await MwpMarketplacePage.prepareWpAdminForScreenshot();
    await expect(
      await browser.checkFullPageScreen(`mwp-admin.marketplace.license_set.${process.env.PHP_VERSION}${trunkSuffix}`)
    ).toEqual(0);
  });

  it('should install and activate a premium plugin successfully', async () => {
    await MwpMarketplacePage.openMarketplacePluginsTab();
    await MwpMarketplacePage.installPlugin('SEOWebVitals');
    await MwpMarketplacePage.activateInstalledPlugin();

    expect(await $('tr.active[data-slug="SEOWebVitals"]').isExisting()).toBeTruthy();
  });

  it('should bulk install and activate plugins correctly', async () => {
    await MwpMarketplacePage.open();
    await MwpMarketplacePage.openMarketplacePluginsTab();
    const installedPlugins = await MwpMarketplacePage.bulkInstallMatomoPlugins();
    await MwpMarketplacePage.bulkActivateMatomoPlugins(installedPlugins);
  });
});
