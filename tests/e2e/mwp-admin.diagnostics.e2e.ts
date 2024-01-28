/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { expect, browser } from '@wdio/globals';
import MwpDiagnosticsPage from './pageobjects/mwp-admin/diagnostics.page.js';
import Website from './website.js';

describe('MWP Admin > Diagnostics', () => {
  const trunkSuffix = process.env.WORDPRESS_VERSION === 'trunk' ? '.trunk' : '';

  before(async () => {
    await Website.login();
  });

  it('should load the system report tab correctly', async () => {
    await MwpDiagnosticsPage.open();

    if (!process.env.PHP_VERSION) {
      throw new Error('Unexpected: PHP_VERSION environment variable cannot be found.');
    }

    await MwpDiagnosticsPage.prepareWpAdminForScreenshot();
    await expect(
      await browser.checkFullPageScreen(`mwp-admin.diagnostics.system-report.${process.env.PHP_VERSION}${trunkSuffix}`)
    ).toBeLessThan(0.01);
  });

  it('should load the troubleshooting tab correctly', async () => {
    await MwpDiagnosticsPage.open();
    await MwpDiagnosticsPage.openTroubleshootingTab();

    await MwpDiagnosticsPage.prepareWpAdminForScreenshot();
    await expect(
      await browser.checkFullPageScreen(`mwp-admin.diagnostics.troubleshooting${trunkSuffix}`)
    ).toEqual(0);
  });
});
