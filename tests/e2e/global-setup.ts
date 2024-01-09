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
    await this.trackVisitsInPast();
    await this.trackRealtimeVisitWithLocation();
    await this.runArchiving();
  }

  async runArchiving() {
    try {
      await MatomoApi.call('POST', 'CoreAdminHome.runCronArchiving');
    } catch (e) {
      // this API method currently prints out some PHP warnings due to a flush() that's
      // in CronArchive.php. WordPress adds headers after dispatching a REST API method,
      // causing the warnings to emit.

      // ignore
    }
  }

  async trackRealtimeVisitWithLocation() {
    if (await this.isRealtimeVisitAlreadyTracked()) {
      return;
    }

    const baseUrl = await Website.baseUrl();

    // track an action
    await MatomoApi.track('1', new URLSearchParams({
      action_name: 'Test page',
      url: `${baseUrl}/test/page`,
      cip: '123.45.0.0',
      country: 'kr',
      lat: '35.904232',
      long: '127.391840',
    }));
  }

  async trackVisitsInPast() {
    if (await this.isVisitAlreadyTracked()) {
      return;
    }

    const baseUrl = await Website.baseUrl();

    // track first visit
    await MatomoApi.track('1', new URLSearchParams({
      action_name: 'Test page',
      url: `${baseUrl}/test/page`,
      cdt: `${this.getDateOfVisitTrackedInPast()} 14:00:00`,
      urlref: 'http://someotherwebsite.com/page.html',
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
      urlref: 'http://facebook.com/some/fbookpage',
    }));

    // third visit with campaign referrer
    await MatomoApi.track('1', new URLSearchParams({
      action_name: 'Test page',
      url: `${baseUrl}/test/page?mtm_campaign=test`,
      cdt: `${this.getDateOfVisitTrackedInPast()} 18:00:00`,
    }));

    // fourth visit with search engine referrer
    await MatomoApi.track('1', new URLSearchParams({
      action_name: 'Test page',
      url: `${baseUrl}/test/page`,
      cdt: `${this.getDateOfVisitTrackedInPast()} 19:00:00`,
      urlref: `http://google.com/search?q=${encodeURIComponent('search term')}`,
    }));
  }

  private async isRealtimeVisitAlreadyTracked() {
    const realTimeVisit = await MatomoApi.call('GET', 'Live.getLastVisitsDetails', new URLSearchParams({
      idSite: '1',
      date: 'today',
      period: 'week',
      filter_limit: '1',
      format: 'json',
    }));

    return realTimeVisit instanceof Array && realTimeVisit.length > 0;
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
