/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { $ } from '@wdio/globals';
import MatomoReportingPage from '../../matomo-reporting.page.js';

class OutlinksPage extends MatomoReportingPage {
  async open() {
    const result = await super.open('General_Actions.General_Outlinks');

    await this.waitForActionsTables();

    return result;
  }
}

export default new OutlinksPage();
