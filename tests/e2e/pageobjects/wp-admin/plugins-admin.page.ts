/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import Page from '../page.js';

class PluginsAdminPage extends Page {
  async open() {
    return await super.open('/wp-admin/plugins.php');
  }
}

export default new PluginsAdminPage();
