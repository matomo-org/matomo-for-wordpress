/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { browser, $ } from '@wdio/globals';
import Page from './page.js';

export default class MatomoPage extends Page {
  async hideDateSelectorDate() {
    await browser.execute(function () {
      $('#periodString a#date').text('');
    });
  }
}
