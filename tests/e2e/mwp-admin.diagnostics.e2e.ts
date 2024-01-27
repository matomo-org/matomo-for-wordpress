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
  before(async () => {
    await Website.login();
  });

  it('should load the system report tab correctly', async () => {
    await MwpDiagnosticsPage.open();

    await MwpDiagnosticsPage.prepareWpAdminForScreenshot();
    await expect(
      await browser.checkFullPageScreen('mwp-admin.diagnostics.system-report')
    ).toEqual(0);
  });

  it('should load the troubleshooting tab correctly', async () => {
    await MwpDiagnosticsPage.open();
    await MwpDiagnosticsPage.openTroubleshootingTab();

    await MwpDiagnosticsPage.prepareWpAdminForScreenshot();
    await expect(
      await browser.checkFullPageScreen('mwp-admin.diagnostics.troubleshooting')
    ).toEqual(0);
  });
});
