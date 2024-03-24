/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { $, browser } from '@wdio/globals';
import * as querystring from 'querystring';
import MwpPage from './page.js';

class MwpSummaryPage extends MwpPage {
  async open() {
    return await this.openWith();
  }

  async openWith(params: Record<string, string> = {}) {
    const query = querystring.stringify({ ...params, page: 'matomo-summary', 'force-past-date': '1' });
    return await super.open(`/wp-admin/admin.php?${query}`);
  }

  async changePeriod(periodDescriptor: string) {
    await $(`a.button=${periodDescriptor}`).click();
  }

  async pinReport(index: number) {
    const boxes = await $$('.postbox');
    const pin = await boxes[index].$('button.handlediv');
    await pin.click();

    await browser.waitUntil(async () => {
      return await browser.execute(async () => window.jQuery('.notice.notice-success:contains(Dashboard updated.)').length);
    });
  }
}

export default new MwpSummaryPage();
