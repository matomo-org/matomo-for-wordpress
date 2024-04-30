/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { $, browser } from '@wdio/globals';
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

    // remove Matomo plugin versions from screenshot so tests won't fail every time a plugin updates
    await browser.execute(() => {
      var matomoPlugins = [];

      var $cells = window.jQuery('th:contains(Plugins)').closest('thead').next().find('td');
      $cells.each((i, e) => {
        var prevHtml = window.jQuery(e).prev().html();
        if (prevHtml && prevHtml.includes('(Matomo Plugin)')) {
          matomoPlugins.push(prevHtml.replace('(Matomo Plugin)', '').replace(/\s+/g, ''));

          window.jQuery(e).html(
            window.jQuery(e).html().replace(/\d+\.\d+\.\d+/g, '')
          );
        }
      });

      var $activePluginsValue = window.jQuery('td:contains(Active Plugins)').next().next();
      $activePluginsValue.html(
        $activePluginsValue.html().replace(new RegExp('(' + matomoPlugins.join('|') + '):\\d+\\.\\d+\\.\\d+', 'gi'), '$1:')
      );
    });

    return result;
  }

  async openTroubleshootingTab() {
    await $('a.nav-tab=Troubleshooting').click();
  }
}

export default new MwpDiagnosticsPage();
