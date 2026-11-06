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
    let result = await super.open(`/wp-admin/admin.php?${query}`);
    await $('.postbox').waitForExist({ timeout: 30000 });
    return result;
  }

  async changePeriod(periodDescriptor: string) {
    // marks the current page, so we can tell when the next one has loaded
    await browser.execute(() => { (window as any).e2eOldPage = true; });
    await $(`a.button=${periodDescriptor}`).click();
    await browser.waitUntil(
      () => browser.execute(() => !(window as any).e2eOldPage && document.readyState === 'complete'),
      { timeout: 30000 }
    );
    await $('.postbox').waitForExist({ timeout: 30000 });
  }

  async pinReport(index: number) {
    await browser.execute((i) => {
      window.jQuery('.postbox button.handlediv a')[i].click();
    }, index);

    await browser.waitUntil(async () => {
      return await browser.execute(async () => window.jQuery('.notice.notice-success:contains(Dashboard updated.)').length);
    });
  }

  async forceShowSuggestion(suggestionId: string) {
    await this.openWith({ mtm_force_suggestion: suggestionId });
  }
}

export default new MwpSummaryPage();
