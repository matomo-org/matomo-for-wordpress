/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { $ } from '@wdio/globals';
import Page from '../page.js';

class MwpSummaryPage extends Page {
  async open() {
    return await super.open('/wp-admin/admin.php?page=matomo-summary');
  }

  async changePeriod(periodDescriptor: string) {
    await $(`a.button=${periodDescriptor}`).click();
  }
}

export default new MwpSummaryPage();
