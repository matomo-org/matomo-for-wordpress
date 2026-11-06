/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { browser } from '@wdio/globals';
import Page from '../page.js';

export default class MwpPage extends Page {
  async open(url: string) {
    const result = await super.open(url);

    await browser.waitUntil(() => browser.execute(() => !!window.jQuery), { timeout: 30000 });

    // idle must hold for consecutive checks, since ajax requests can be chained
    let idleChecks = 0;
    await browser.waitUntil(async () => {
      const isIdle = await browser.execute(() => document.readyState === 'complete'
        && window.jQuery.active === 0
        && document.fonts.status === 'loaded');
      idleChecks = isIdle ? idleChecks + 1 : 0;
      return idleChecks >= 3;
    }, { timeout: 30000, interval: 200 });

    return result;
  }
}
