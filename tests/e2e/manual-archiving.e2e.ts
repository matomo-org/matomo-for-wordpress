/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { browser } from '@wdio/globals';
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

    await browser.pause(3000);
    const visits = await MatomoApi.call('GET', 'VisitsSummary.get', new URLSearchParams({
      idSite: 'all',
      period: 'day',
      date: OverviewPage.getDefaultDate(),
    }));
    console.log('found visits ' + JSON.stringify(visits, null, 2));

    await MatomoCli.call('core:archive', {
      'force-idsites': '1',
      'force-date-range': 'yesterday,today',
      'force-periods': 'day',
    });

    // wait until the data for the visits tracked in the past looks correct
    await browser.waitUntil(async () => {
      const visits = await MatomoApi.call('GET', 'VisitsSummary.get', new URLSearchParams({
        idSite: '1',
        period: 'day',
        date: OverviewPage.getDefaultDate(),
      }));
      console.log('found visits ' + visits.nb_visits);
      return visits.nb_visits === 7;
    }, { timeout: 60000 });
  });
});
