/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { browser, expect } from '@wdio/globals';
import GlobalSetup from './global-setup.js';
import Website from './website.js';
import DiagnosticsPage from './pageobjects/mwp-admin/diagnostics.page.js';
import MatomoIniConfig from './apiobjects/matomo.ini.js';

describe('Manual Archiving', () => {
  before(async () => {
    await Website.login();
    await GlobalSetup.setUp();

    // logs will appear in debug.log
    await MatomoIniConfig.set('log', 'log_writers', ['file']);
    await MatomoIniConfig.set('log', 'log_level', 'debug');

    await MatomoIniConfig.set('General', 'time_before_today_archive_considered_outdated', 0);
  });

  after(async () => {
    await MatomoIniConfig.remove('log', 'log_writers[]');
    await MatomoIniConfig.remove('log', 'log_level');
    await MatomoIniConfig.remove('General', 'time_before_today_archive_considered_outdated');
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
  });
});
