/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { $, browser } from '@wdio/globals';
import MatomoReportingPage from '../../matomo-reporting.page.js';

class TransitionsPage extends MatomoReportingPage {
  async open() {
    const result = await super.open('General_Actions.Transitions_Transitions');

    await browser.pause(500);
    await browser.waitUntil(
      async () => !(await $('#transitions_inline_loading').isDisplayed()),
    );
    await browser.pause(500);

    return result;
  }
}

export default new TransitionsPage();
