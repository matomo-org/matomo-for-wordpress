/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { $ } from '@wdio/globals';
import MwpPage from './page.js';

class MwpMarketplacePage extends MwpPage {
  async open() {
    return await super.open('/wp-admin/admin.php?page=matomo-marketplace');
  }

  async openInstallPluginsTab() {
    await $('a.nav-tab=Install Plugins').click();
  }

  async openSubscriptionsTab() {
    await $('a.nav-tab=Subscriptions').click();
  }
}

export default new MwpMarketplacePage();
