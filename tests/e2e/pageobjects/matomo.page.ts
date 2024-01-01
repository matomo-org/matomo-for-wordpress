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

  async hideDateSelectorDate() {
    await browser.execute(function () {
      $('#periodString a#date').text('');
    });
  }
}
