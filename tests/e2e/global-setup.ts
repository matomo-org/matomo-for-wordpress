/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import fetch from 'node-fetch';
import MatomoApi from './apiobjects/matomo.api.js';
import Website from './website.js';

class GlobalSetup {
  async setUp() {
    await this.trackVisitInPast();

    await this.runArchiving();
  }

  async runArchiving() {
    const url = `${await Website.baseUrl()}/wp-admin/admin.php?page=matomo-systemreport&tab=troubleshooting`;

    const response = await fetch(url, {
      method: 'POST',
      body: new URLSearchParams({
        _wpnonce: await Website.getWpNonce(),
        _wp_http_referer: url,
        matomo_troubleshooting_action_archive_now: 'Archive reports',
      }),
    });

    if (response.status < 200 || response.status >= 300) {
      throw new Error(`archiving failed: ${await response.text()}`);
    }
  }

  async trackVisitInPast() {
    if (await this.isVisitAlreadyTracked()) {
      return;
    }

    const baseUrl = await Website.baseUrl();

    await MatomoApi.track('1', new URLSearchParams({
      action_name: 'Test page',
      h: '15',
      m: '16',
      s: '49',
      url: `${baseUrl}/test/page`,
    }));
  }

  private async isVisitAlreadyTracked() {
    const visitInDay = await MatomoApi.call('GET', 'Live.getLastVisitsDetails', new URLSearchParams({
      idSite: '1',
      date: this.getDateOfVisitTrackedInPast(),
      period: 'day',
      filter_limit: '1',
    }));

    return visitInDay.length > 0;
  }

  getDateOfVisitTrackedInPast() {
    return '2023-12-20';
  }
}

export default new GlobalSetup();
