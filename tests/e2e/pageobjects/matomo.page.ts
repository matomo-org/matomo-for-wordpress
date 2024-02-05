/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { browser, $ } from '@wdio/globals';
import Page from './page.js';
import querystring from "querystring";
import * as path from "path";

export default class MatomoPage extends Page {

  async open(path: string) {
    const result = super.open(path);
    await this.addStylesToPage('table.entityTable tbody tr:hover td { background-color: unset !important; }');
    return result;
  }

  async unfocus() {
    await browser.execute(() => {
      $('#logo')[0].scrollIntoView();
    });

    // try to move mouse to another element to trigger mouseleave events
    // for code with hover effects
    try {
      await $('#logo').moveTo();
    } catch (e) {
      // ignore
    }

    await browser.execute(() => {
      $('#logo')[0].focus();
    });

    await browser.pause(250);
  }

  async hideDateSelectorDate() {
    await browser.execute(function () {
      $('#periodString a#date').text('');
    });
  }
}
