/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import MatomoApi from './apiobjects/matomo.api.js';
import Website from './website.js';

// TODO: need to document how to write an e2e test. eg, individual tests
// should ideally not add data, data should be added beforehand in global
// setup, etc.
class GlobalSetup {
  async setUp() {
    await this.trackVisitInPast();

    await this.runArchiving();
  }

  async runArchiving() {
    try {
      await MatomoApi.call('POST', 'CoreAdminHome.runCronArchiving');
    } catch (e) {
      // TODO: create an issue for the following
      // this API method currently prints out some PHP warnings due to a flush() that's
      // in CronArchive.php. WordPress adds headers after dispatching a REST API method,
      // causing an error.

      // ignore
    }
  }

  async trackVisitInPast() {
    if (await this.isVisitAlreadyTracked()) {
      return;
    }

    const baseUrl = await Website.baseUrl();

    // track first action
    await MatomoApi.track('1', new URLSearchParams({
      action_name: 'Test page',
      url: `${baseUrl}/test/page`,
      cdt: `${this.getDateOfVisitTrackedInPast()} 14:00:00`,
    }));

    // track download
    await MatomoApi.track('1', new URLSearchParams({
      download: `${baseUrl}/test/page/download.pdf`,
      cdt: `${this.getDateOfVisitTrackedInPast()} 14:01:00`,
    }));

    // track outlink
    await MatomoApi.track('1', new URLSearchParams({
      link: 'http://anothersite.com/site',
      cdt: `${this.getDateOfVisitTrackedInPast()} 14:02:00`,
    }));

    // track site search
    await MatomoApi.track('1', new URLSearchParams({
      search: 'asearch',
      cdt: `${this.getDateOfVisitTrackedInPast()} 14:03:00`,
    }));

    // another visit that bounces
    await MatomoApi.track('1', new URLSearchParams({
      action_name: 'Test page',
      url: `${baseUrl}/test/page`,
      cdt: `${this.getDateOfVisitTrackedInPast()} 17:00:00`,
    }));
  }

  private async isVisitAlreadyTracked() {
    const visitInDay = await MatomoApi.call('GET', 'Live.getLastVisitsDetails', new URLSearchParams({
      idSite: '1',
      date: this.getDateOfVisitTrackedInPast(),
      period: 'week',
      filter_limit: '1',
      format: 'json',
    }));

    return visitInDay instanceof Array && visitInDay.length > 0;
  }

  getDateOfVisitTrackedInPast() {
    return '2023-12-20';
  }
}

export default new GlobalSetup();
