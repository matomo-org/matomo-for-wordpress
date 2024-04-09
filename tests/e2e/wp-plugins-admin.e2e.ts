/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { expect, browser } from '@wdio/globals';
import Website from './website.js';
import PluginsAdmin from './pageobjects/wp-admin/plugins-admin.page.js';

describe('WordPress Customizations > Plugins Admin', () => {
  const trunkSuffix = process.env.WORDPRESS_VERSION === 'trunk' ? '.trunk' : '';

  before(async () => {
    await Website.login();
  });

  it('should show the data deletion setting in the plugins admin', async () => {
    await PluginsAdmin.open();

    await PluginsAdmin.prepareWpAdminForScreenshot();
    await PluginsAdmin.hideNonMatomoRows();
    await PluginsAdmin.hideNotifications();
    await PluginsAdmin.hidePluginFilters();
    expect(
      await browser.checkFullPageScreen(`wp-customizations.plugins-admin.data-deletion${trunkSuffix}`)
    ).toEqual(0);
  });

  it('should link to the data deletion settings page', async () => {
    const href = await browser.execute(() => window.jQuery('a#mwp-data-deletion-settings').attr('href'));
    expect(href).toMatch(/\/wp-admin\/admin\.php\?page=matomo-settings&tab=advanced$/);
  });
});
