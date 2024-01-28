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

    await browser.waitUntil(() => browser.execute(() => !!window.jQuery));
    await browser.pause(1000);

    return result;
  }
}
