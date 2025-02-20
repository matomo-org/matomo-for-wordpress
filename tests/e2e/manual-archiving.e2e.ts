/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import GlobalSetup from './global-setup';
import Website from './website';
import DiagnosticsPage from "./pageobjects/mwp-admin/diagnostics.page";
import {browser, expect} from "@wdio/globals";

describe('Manual Archiving', () => {
  before(async () => {
    await Website.login();
    await GlobalSetup.setUp();
  });

  it('should run archiving successfully when manual archiving is initiated in troubleshooting', async () => {
    await DiagnosticsPage.open();
    await DiagnosticsPage.openTroubleshootingTab();
    await $('input[name="matomo_troubleshooting_action_archive_now"]').click();
    await browser.waitUntil(async () => {
      return await browser.execute(() => {
        return window.jQuery('.notice:contains(Matomo Archiving completed successfully!)').length > 0;
      });
    });

    await expect(
      await browser.checkFullPageScreen('matomo-troubleshooting.manual-archive')
    ).toEqual(0);
  });
});
