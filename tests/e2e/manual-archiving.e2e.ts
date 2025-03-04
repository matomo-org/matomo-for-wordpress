/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { browser } from '@wdio/globals';
import fetch from 'node-fetch';
import GlobalSetup from './global-setup.js';
import Website from './website.js';
import MatomoIniConfig from './apiobjects/matomo.ini.js';
import MatomoApi from './apiobjects/matomo.api.js';
import MatomoCli from './apiobjects/matomo.cli.js';
import OverviewPage from './pageobjects/matomo-reporting/visitors/overview.page.js';

describe('Manual Archiving', function () {
  this.timeout(360000);

  before(async () => {
    await Website.login();
    await GlobalSetup.setUp();

    // logs will appear in debug.log
    await MatomoIniConfig.set('log', 'log_writers', ['file']);
    await MatomoIniConfig.set('log', 'log_level', 'info');
  });

  after(async () => {
    await MatomoIniConfig.remove('log', 'log_writers[]');
    await MatomoIniConfig.remove('log', 'log_level');
  });

  it('should run archiving successfully', async () => {
    await browser.waitUntil(async () => {
      const params = new URLSearchParams();
      params.set('idSite', '1');
      params.set('dates', OverviewPage.getDefaultDate());
      params.set('period', 'day');
      await MatomoApi.call('POST', 'CoreAdminHome.invalidateArchivedReports', params);

      await MatomoCli.call('core:archive', {
        'force-idsites': '1',
        'force-date-range': `${OverviewPage.getDefaultDate()},${OverviewPage.getDefaultDate()}`,
        'force-periods': 'day',
      });

      await MatomoCli.call('core:archive', {
        'force-idsites': '1',
        'force-date-range': 'yesterday,today',
        'force-periods': 'day',
      });

      // check that the data looks correct (for some reason, archiving randomly results in an incorrect,
      // invalidated archive)
      const visits = await MatomoApi.call('GET', 'VisitsSummary.get', new URLSearchParams({
        idSite: '1',
        period: 'day',
        date: OverviewPage.getDefaultDate(),
      }));


      if (visits.nb_visits !== 7) {
        // Working around https://github.com/matomo-org/matomo/issues/23085.
        // If archive data selected is inaccurate, delete the entire archive table for the month,
        // and re-archive.
        await fetch(`${await Website.baseUrl()}/wp-admin/admin-ajax.php`, {
          method: 'POST',
          headers:{
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: new URLSearchParams({
            action: 'test_remove_archive_table',
            date: OverviewPage.getDefaultDate(),
          }),
        });
      }
      return visits.nb_visits === 7;
    }, { timeout: 120000 });
  });
});
