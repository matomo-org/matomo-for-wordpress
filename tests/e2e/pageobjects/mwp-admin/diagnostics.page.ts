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
  async open(overrideUrl?: string) {
    const result = await super.open(overrideUrl || '/wp-admin/admin.php?page=matomo-systemreport');
    await $('a.nav-tab').waitForExist();
    await this.normalizePageContents();
    return result;
  }

  async openTroubleshootingTab() {
    await browser.execute(() => {
      window.jQuery('a.nav-tab:contains(Troubleshooting)')[0].click();
    });
    await $('#matomo_troubleshooting_update_from').waitForExist();
    await browser.pause(500);
  }

  private async normalizePageContents() {
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

      window.jQuery('tbody#logs_body > tr').remove();

      var versionRows = [
        'matomoinstallversion',
        'matomopluginversion',
        'versionhistory',
        'matomoanalytics-ethicalstatspowerfulinsights',
        'activeplugins',
      ];
      var currentVersion = window.jQuery('tr#matomo-diagnostic-matomopluginversion>td:nth-child(2)').text().trim();
      versionRows.forEach((id) => {
        const $cell = window.jQuery(`tr#matomo-diagnostic-${id}>td:nth-child(2)`);
        if ($cell.length) {
          $cell.html($cell.html().replaceAll(currentVersion, 'CURRENT_VERSION'));
        }
      });

      var coreVersionRows = [
        'coreversion',
        'matomoversion',
      ]
      coreVersionRows.forEach((id) => {
        var $coreVersionCell = window.jQuery(`tr#matomo-diagnostic-${id}>td:nth-child(2)`);
        if ($coreVersionCell.length) {
          $coreVersionCell.html($coreVersionCell.html().replace(/\d+\.\d+\.\d+/g, 'CURRENT_CORE_VERSION'));
        }
      });

      var $currentWpVersion = window.jQuery('tr#matomo-diagnostic-wordpressversion>td:nth-child(2)');
      if ($currentWpVersion.length) {
        $currentWpVersion.html($currentWpVersion.html().replace(/[\da-zA-Z._-]+/g, 'CURRENT_WP_VERSION'));
      }

      window.jQuery('#matomo_system_report_info').val(
        window.jQuery('#matomo_system_report_info')
          .val()
          .replace(/Matomo Plugin Version: \d+\.\d+\.\d+/g, 'Matomo Plugin Version: CURRENT_CORE_VERSION')
          .replace(/Matomo Install Version: \d+\.\d+\.\d+/g, 'Matomo Install Version: CURRENT_INSTALL_VERSION')
          .replace(/Matomo Version: \d+\.\d+\.\d+/g, 'Matomo Version: CURRENT_MATOMO_VERSION')
          .replace(/\(Install date: \d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\)/g, '(Install date: INSTALL_DATE)')
      );

      var timestampRows = [
        'lastsettingsupdate',
        'lasttrackingsettingsupdate',
        'time',
      ];

      timestampRows.forEach((id) => {
        const $cell = window.jQuery(`tr#matomo-diagnostic-${id}>td:nth-child(2)`);
        if ($cell.length) {
          $cell.html($cell.html().replace(/\d+/g, 'TIMESTAMP_REMOVED'));
        }
      });
    });
  }
}

export default new MwpDiagnosticsPage();
export { MwpDiagnosticsPage as MwpDiagnosticsPageType };
