/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import Page from '../page.js';
import {browser} from "@wdio/globals";

class PluginsAdminPage extends Page {
  async open() {
    return await super.open('/wp-admin/plugins.php');
  }

  async hideNonMatomoRows() {
    await browser.execute(() => {
      window.jQuery('tr[data-slug]:not([data-slug="matomo"])').hide();
    });
  }

  async hideNotifications() {
    await browser.execute(() => {
      window.jQuery('div[id="message"]').hide();
    });
  }

  async hidePluginFilters() {
    await browser.execute(() => {
      window.jQuery('.subsubsub').hide();
    });
  }

  async hidePluginVersion() {
    await browser.execute(() => {
      window.jQuery('.plugin-version-author-uri').each(function () {
        window.jQuery(this).html(
          window.jQuery(this).html().replace(/Version \d+\.\d+\.\d+/g, 'Version REMOVED')
        );
      });
    });
  }
}

export default new PluginsAdminPage();
