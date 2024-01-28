/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { $ } from '@wdio/globals';
import MwpPage from './page.js';

class MwpDiagnosticsPage extends MwpPage {
  async open() {
    const result = await super.open('/wp-admin/admin.php?page=matomo-systemreport');

    await browser.execute(() => {
      // remove dates from every table cell
      window.jQuery('.matomo-systemreport td').each((i, e) => {
        window.jQuery(e).html(
          window.jQuery(e).html().replace(/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}( \([0-9a-zA-Z\s-]+\))?/g, 'REMOVED'),
        );
      });
    });

    return result;
  }

  async openTroubleshootingTab() {
    await $('a.nav-tab=Troubleshooting').click();
  }
}

export default new MwpDiagnosticsPage();
